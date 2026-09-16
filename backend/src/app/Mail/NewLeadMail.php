<?php

namespace App\Mail;

use App\Models\LeadRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Уведомление менеджеру о новой заявке с сайта (для MailHog в dev).
 */
class NewLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param LeadRequest $lead Созданная заявка
     */
    public function __construct(public LeadRequest $lead)
    {
    }

    /**
     * Тема и адресат письма.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Новая заявка с сайта РК ПРОФИ #' . $this->lead->id,
        );
    }

    /**
     * Шаблон тела письма.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    /**
     * Прикрепляет файлы заявки к письму менеджеру.
     *
     * Поле `LeadRequest->attachments` хранит метаданные в формате:
     * [
     *   'attachment' => [ ['path'=>..., 'original_name'=>..., ...], ...],
     *   'company_card' => [ ['path'=>..., 'original_name'=>..., ...], ...],
     * ]
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $lead = $this->lead;

        $stored = $lead->attachments;
        if (!is_array($stored)) {
            return [];
        }

        /** @var list<array{path:string,original_name:string}> $files */
        $result = [];
        $result = array_merge(
            $result,
            $this->mapStoredFilesToAttachments($stored['attachment'] ?? [])
        );
        $result = array_merge(
            $result,
            $this->mapStoredFilesToAttachments($stored['company_card'] ?? [])
        );

        return $result;
    }

    /**
     * Преобразует метаданные файлов заявки в прикрепления письма.
     *
     * @param array<int, mixed> $files
     * @return array<int, Attachment>
     */
    private function mapStoredFilesToAttachments(array $files): array
    {
        $result = [];

        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }

            $path = $file['path'] ?? null;
            $originalName = $file['original_name'] ?? null;

            if (!is_string($path) || $path === '') {
                continue;
            }

            $displayName = is_string($originalName) && $originalName !== '' ? $originalName : 'file';

            // Файлы лежат в диске `public`, каталог задан в CreateLeadRequestAction.
            $result[] = Attachment::fromStorageDisk('public', $path)
                ->as($displayName);
        }

        return $result;
    }

    /**
     * Собирает HTML-тело письма с данными заявки.
     *
     * @return string
     */
    private function buildHtml(): string
    {
        $lead = $this->lead;

        return '<h1>Новая заявка #' . e((string) $lead->id) . '</h1>'
            . '<p><strong>Имя:</strong> ' . e($lead->name) . '</p>'
            . '<p><strong>Телефон:</strong> ' . e($lead->phone) . '</p>'
            . '<p><strong>Email:</strong> ' . e($lead->email) . '</p>'
            . '<p><strong>Сообщение:</strong><br>' . nl2br(e($lead->message)) . '</p>';
    }
}
