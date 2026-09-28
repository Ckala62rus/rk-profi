<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Models\LeadRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Создание заявки с публичной формы (файлы + запись в БД + письмо).
 */
class CreateLeadRequestAction
{
    /**
     * Сохраняет заявку и вложения на диск, отправляет уведомление на email.
     *
     * @param array{
     *     name: string,
     *     phone: string,
     *     email: string,
     *     message: string,
     *     ip?: string|null,
     *     user_agent?: string|null,
     * } $data
     * @param array<int, UploadedFile> $attachments Файлы «Материалы к заявке»
     * @param array<int, UploadedFile> $companyCards Файлы «Карточка предприятия»
     * @return LeadRequest
     */
    public function execute(array $data, array $attachments = [], array $companyCards = []): LeadRequest
    {
        $lead = DB::transaction(function () use ($data, $attachments, $companyCards) {
            $stored = [
                'attachment' => $this->storeFiles($attachments, 'leads/attachments'),
                'company_card' => $this->storeFiles($companyCards, 'leads/company_cards'),
            ];

            return LeadRequest::query()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'message' => $data['message'],
                'attachments' => $stored,
                'status' => LeadStatus::New,
                'email_sent_at' => null,
                'email_delivery_status' => 'pending',
                'ip' => $data['ip'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ]);
        });

        app(QueueLeadNotificationAction::class)->execute($lead);

        return $lead->fresh();
    }

    /**
     * Сохраняет список загруженных файлов и возвращает метаданные.
     *
     * @param array<int, UploadedFile> $files
     * @param string $directory Каталог в диске public
     * @return list<array{path: string, original_name: string, size: int, mime: string|null}>
     */
    private function storeFiles(array $files, string $directory): array
    {
        $result = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store($directory, 'public');

            $result[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize() ?: 0,
                'mime' => $file->getClientMimeType(),
            ];
        }

        return $result;
    }
}
