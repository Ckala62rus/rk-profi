<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация сохранения контактов сайта в админке.
 */
class UpdateContactsSettingsRequest extends FormRequest
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
            'phones' => ['nullable', 'array'],
            'emails' => ['nullable', 'array'],
            'addresses' => ['nullable', 'array'],
            'map_embed_url' => ['nullable', 'string', 'max:2000'],
            'office_photo_url' => ['nullable', 'string', 'max:2000'],
            'copyright' => ['nullable', 'string', 'max:500'],
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
            'phones.array' => 'Телефоны должны быть списком.',
            'emails.array' => 'Email должны быть списком.',
            'addresses.array' => 'Адреса должны быть списком.',
            'map_embed_url.string' => 'Ссылка на карту должна быть строкой.',
            'map_embed_url.max' => 'Ссылка на карту не должна превышать :max символов.',
            'office_photo_url.string' => 'URL фото офиса должен быть строкой.',
            'office_photo_url.max' => 'URL фото офиса не должен превышать :max символов.',
            'copyright.string' => 'Copyright должен быть строкой.',
            'copyright.max' => 'Copyright не должен превышать :max символов.',
        ];
    }

    /**
     * Нормализует map_embed_url: из HTML iframe достаёт src.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $raw = $this->input('map_embed_url');
        if (! is_string($raw) || $raw === '') {
            return;
        }

        $trimmed = trim($raw);
        if (preg_match('/src=["\']([^"\']+)["\']/i', $trimmed, $matches) === 1) {
            $trimmed = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $this->merge([
            'map_embed_url' => $trimmed,
        ]);
    }
}
