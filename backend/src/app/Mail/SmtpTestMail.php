<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Явно запущенная оператором проверка SMTP без данных клиентов.
 */
class SmtpTestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Тема диагностического письма.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Проверка SMTP — ' . config('app.name'),
        );
    }

    /**
     * Безопасное статическое содержимое без данных заявок и секретов.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Это тестовое письмо SMTP от сайта ' . e((string) config('app.name')) . '.</p>'
                . '<p>Отправлено: ' . e(now()->toIso8601String()) . '</p>',
        );
    }
}
