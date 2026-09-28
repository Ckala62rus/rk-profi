<?php

namespace App\Console\Commands;

use App\Models\LeadRequest;
use Illuminate\Console\Command;

/**
 * Делает застрявшие состояния очереди доступными для ручного retry.
 */
class RecoverStaleLeadDeliveryCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'leads:recover-delivery
                            {--minutes=30 : Age in minutes after which queued/sending delivery is stale}';

    /**
     * @var string
     */
    protected $description = 'Mark stale queued or sending lead notifications as failed';

    /**
     * Помечает задачи, зависшие дольше допустимого интервала.
     *
     * @return int
     */
    public function handle(): int
    {
        $minutes = filter_var($this->option('minutes'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1440],
        ]);

        if ($minutes === false) {
            $this->error('Option --minutes must be an integer between 1 and 1440.');

            return self::FAILURE;
        }

        $cutoff = now()->subMinutes($minutes);
        $count = LeadRequest::query()
            ->where(function ($query) use ($cutoff): void {
                $query->where(function ($query) use ($cutoff): void {
                    $query->where('email_delivery_status', 'queued')
                        ->where('email_queued_at', '<=', $cutoff);
                })->orWhere(function ($query) use ($cutoff): void {
                    $query->where('email_delivery_status', 'sending')
                        ->where('email_started_at', '<=', $cutoff);
                });
            })
            ->update([
                'email_delivery_status' => 'failed',
                'email_failed_at' => now(),
                'email_error' => "Превышено время ожидания доставки ({$minutes} мин.). Повторите отправку из админки.",
                'updated_at' => now(),
            ]);

        $this->info("Recovered {$count} stale lead notification(s).");

        return self::SUCCESS;
    }
}
