<?php

namespace App\Jobs;

use App\Actions\QueueLeadNotificationAction;
use App\Mail\NewLeadMail;
use App\Models\LeadRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Асинхронная отправка уведомления менеджеру о новой заявке.
 */
class SendNewLeadMailJob implements ShouldQueue
{
    use Queueable;

    /** Количество попыток SMTP-доставки до окончательной ошибки. */
    public int $tries = 3;

    /** Задержки между повторными попытками в секундах. */
    public array $backoff = [30, 180];

    /** Должно быть меньше database retry_after (90 секунд). */
    public int $timeout = 60;

    /** Идентификатор заявки в новом формате задания. */
    public int $leadId = 0;

    /** Поколение доставки, защищающее от старой job после retry. */
    public int $deliveryVersion = 0;

    /**
     * Заявка в формате задания до внедрения аудита доставки.
     *
     * Поле оставлено для восстановления старых payload из jobs/failed_jobs.
     */
    public ?LeadRequest $lead = null;

    /**
     * @param int $leadId Идентификатор заявки
     * @param int $deliveryVersion Поколение доставки
     */
    public function __construct(int $leadId = 0, int $deliveryVersion = 0)
    {
        $this->leadId = $leadId;
        $this->deliveryVersion = $deliveryVersion;
    }

    /**
     * Отправляет письмо и фиксирует успешную доставку.
     *
     * @return void
     *
     * @throws Throwable При ошибке SMTP или вложений Laravel повторит задачу
     */
    public function handle(): void
    {
        if ($this->isLegacyPayload()) {
            // До внедрения аудита job сериализовала всю модель в свойство lead.
            // При повторе старой job ставим современную версионированную задачу,
            // а не пытаемся отправить письмо по устаревшему формату напрямую.
            app(QueueLeadNotificationAction::class)->execute($this->lead);

            return;
        }

        $lead = DB::transaction(function (): ?LeadRequest {
            $lockedLead = LeadRequest::query()
                ->lockForUpdate()
                ->find($this->leadId);

            if ($lockedLead === null
                || $lockedLead->email_delivery_version !== $this->deliveryVersion
                || ! in_array($lockedLead->email_delivery_status, ['queued', 'sending'], true)) {
                return null;
            }

            $lockedLead->forceFill([
                'email_delivery_status' => 'sending',
                'email_started_at' => now(),
                'email_attempts' => $lockedLead->email_attempts + 1,
                'email_error' => null,
            ])->save();

            return $lockedLead;
        });

        if ($lead === null) {
            return;
        }

        $recipient = $lead->email_recipient ?: (string) config('mail.lead_notify_address');
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Не задан корректный адрес получателя уведомлений о заявках.');
        }

        Mail::to($recipient)->send(new NewLeadMail($lead));

        LeadRequest::query()
            ->whereKey($this->leadId)
            ->where('email_delivery_version', $this->deliveryVersion)
            ->update([
                'email_delivery_status' => 'sent',
                'email_sent_at' => now(),
                'email_failed_at' => null,
                'email_error' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Определяет задачу, сериализованную версией до deliveryVersion.
     *
     * @return bool
     */
    private function isLegacyPayload(): bool
    {
        return $this->leadId === 0 && $this->lead !== null;
    }

    /**
     * Вызывается Laravel после исчерпания повторов задачи.
     *
     * @param Throwable $exception Последняя ошибка доставки
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        LeadRequest::query()
            ->whereKey($this->leadId)
            ->where('email_delivery_version', $this->deliveryVersion)
            ->where('email_delivery_status', 'sending')
            ->update([
                'email_delivery_status' => 'failed',
                'email_failed_at' => now(),
                'email_error' => mb_substr($exception->getMessage(), 0, 1000),
                'updated_at' => now(),
            ]);
    }
}
