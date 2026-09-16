<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Jobs\SendNewLeadMailJob;
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
                // Поле выставляем только после успешной отправки письма.
                // Если отправка упадёт — заявка остаётся созданной, а флаг останется null.
                'email_sent_at' => null,
                'ip' => $data['ip'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ]);
        });

        try {
            // Письмо и флаг email_sent_at — в очереди (QUEUE_CONNECTION=database).
            SendNewLeadMailJob::dispatch($lead);
        } catch (\Throwable $e) {
            // Не прерываем создание заявки: пользователь должен получить успешный ответ.
            report($e);
        }

        return $lead;
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
