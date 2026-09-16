<?php

namespace App\Jobs;

use App\Mail\NewLeadMail;
use App\Models\LeadRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Асинхронная отправка письма менеджеру о новой заявке.
 *
 * После успешной отправки проставляет `email_sent_at`.
 */
class SendNewLeadMailJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param LeadRequest $lead Созданная заявка
     */
    public function __construct(public LeadRequest $lead)
    {
    }

    /**
     * Отправляет письмо и фиксирует факт отправки.
     *
     * @return void
     *
     * @throws \Throwable При ошибке SMTP/вложений — job уйдёт в failed_jobs
     */
    public function handle(): void
    {
        $notifyTo = config('mail.lead_notify_address');

        Mail::to($notifyTo)->send(new NewLeadMail($this->lead));

        $this->lead->forceFill([
            'email_sent_at' => now(),
        ])->save();
    }
}
