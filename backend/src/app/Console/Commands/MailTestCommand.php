<?php

namespace App\Console\Commands;

use App\Mail\SmtpTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Отправляет контролируемое тестовое письмо для проверки SMTP-конфигурации.
 */
class MailTestCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mail:test
                            {--to= : Recipient email address; required}
                            {--force : Required when APP_ENV is production}';

    /**
     * @var string
     */
    protected $description = 'Send a safe SMTP test email without lead data';

    /**
     * Выполняет безопасную SMTP-проверку.
     *
     * @return int
     */
    public function handle(): int
    {
        $recipient = trim((string) $this->option('to'));

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid recipient with --to=address@example.com.');

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Production delivery requires the explicit --force option.');

            return self::FAILURE;
        }

        if (! $this->confirm("Send a test email to {$recipient}?", true)) {
            $this->warn('No email was sent.');

            return self::SUCCESS;
        }

        try {
            // Явно выбираем SMTP: log/array/failover не должны выдавать себя
            // за успешную проверку соединения с почтовым сервером.
            Mail::mailer('smtp')->to($recipient)->send(new SmtpTestMail());
        } catch (Throwable $exception) {
            report($exception);
            $this->error('SMTP test failed. Check the application logs and MAIL_* variables; do not share their values.');

            return self::FAILURE;
        }

        $this->info("SMTP test email was accepted for delivery to {$recipient}.");

        return self::SUCCESS;
    }
}
