<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Загрузка медиа CMS-страницы: изображение или видео hero.
 */
class UploadPageMediaRequest extends FormRequest
{
    /**
     * Доступ для авторизованного админа.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'kind' => ['required', 'string', 'in:image,video'],
            'field' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Сообщения на русском.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Выберите файл.',
            'file.file' => 'Загрузка должна быть файлом.',
            'kind.required' => 'Укажите тип файла (image или video).',
            'kind.in' => 'Тип файла должен быть image или video.',
            'field.string' => 'Имя поля должно быть строкой.',
            'field.max' => 'Имя поля не должно превышать :max символов.',
        ];
    }

    /**
     * Доп. правила MIME/размера по kind.
     *
     * @param Validator $validator Валидатор
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $file = $this->file('file');
            if ($file === null) {
                return;
            }

            $kind = (string) $this->input('kind');
            $mime = (string) $file->getMimeType();
            $sizeKb = (int) ceil($file->getSize() / 1024);

            if ($kind === 'image') {
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (! in_array($mime, $allowed, true)) {
                    $validator->errors()->add('file', 'Изображение: JPG, PNG, WebP или GIF.');
                }
                if ($sizeKb > 5120) {
                    $validator->errors()->add('file', 'Изображение не должно превышать 5 МБ.');
                }

                return;
            }

            $allowedVideo = ['video/mp4', 'video/webm', 'video/quicktime'];
            if (! in_array($mime, $allowedVideo, true)) {
                $validator->errors()->add('file', 'Видео: MP4, WebM или MOV.');
            }
            // До 50 МБ
            if ($sizeKb > 51200) {
                $validator->errors()->add('file', 'Видео не должно превышать 50 МБ.');
            }
        });
    }
}
