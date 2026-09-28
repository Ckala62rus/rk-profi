<?php

namespace App\Actions;

use App\Jobs\SendNewLeadMailJob;
use App\Models\LeadRequest;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Надёжно ставит уведомление по заявке в database-очередь.
 */
class QueueLeadNotificationAction
{
    /**
     * Допустимые исходные состояния для новой постановки в очередь.
     *
     * @var list<string>
     */
    private const RETRYABLE_STATUSES = ['pending', 'failed', 'dispatch_failed'];

    /**
     * Атомарно фиксирует новое поколение доставки и задачу database-очереди.
     *
     * Блокировка строки не позволяет двум запросам retry создать два письма.
     * В database-очереди запись job попадает в ту же транзакцию, поэтому сбой
     * не оставляет статус «В очереди» без самой задачи.
     *
     * @param LeadRequest $lead Заявка для уведомления
     * @return bool Была ли создана новая задача; false для неактуального retry или ошибки
     */
    public function execute(LeadRequest $lead): bool
    {
        try {
            return DB::transaction(function () use ($lead): bool {
                $lockedLead = LeadRequest::query()
                    ->lockForUpdate()
                    ->find($lead->getKey());

                if ($lockedLead === null
                    || ! in_array($lockedLead->email_delivery_status, self::RETRYABLE_STATUSES, true)) {
                    return false;
                }

                $deliveryVersion = $lockedLead->email_delivery_version + 1;

                $lockedLead->forceFill([
                    'email_delivery_status' => 'queued',
                    'email_delivery_version' => $deliveryVersion,
                    'email_recipient' => (string) config('mail.lead_notify_address'),
                    'email_queued_at' => now(),
                    'email_started_at' => null,
                    'email_sent_at' => null,
                    'email_failed_at' => null,
                    'email_error' => null,
                ])->save();

                SendNewLeadMailJob::dispatch($lockedLead->getKey(), $deliveryVersion);

                return true;
            });
        } catch (Throwable $exception) {
            report($exception);

            // Обновляем лишь по-прежнему доступную для retry запись: успешная
            // параллельная постановка не будет перетёрта ошибкой этого запроса.
            LeadRequest::query()
                ->whereKey($lead->getKey())
                ->whereIn('email_delivery_status', self::RETRYABLE_STATUSES)
                ->update([
                    'email_delivery_status' => 'dispatch_failed',
                    'email_failed_at' => now(),
                    'email_error' => $this->errorMessage($exception),
                    'updated_at' => now(),
                ]);

            return false;
        }
    }

    /**
     * Ограничивает текст технической ошибки, сохраняемый в БД.
     *
     * @param Throwable $exception Ошибка очереди
     * @return string
     */
    private function errorMessage(Throwable $exception): string
    {
        return mb_substr($exception->getMessage(), 0, 1000);
    }
}
