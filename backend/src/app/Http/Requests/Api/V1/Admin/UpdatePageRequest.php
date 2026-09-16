<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления контентной страницы (CMS) в админке.
 */
class UpdatePageRequest extends FormRequest
{
    /**
     * Доступ только для авторизованного админа (middleware Sanctum).
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
            'title' => ['sometimes', 'string', 'max:190'],
            'blocks' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Сообщения об ошибках на русском.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.string' => 'Заголовок должен быть строкой.',
            'title.max' => 'Заголовок не должен превышать :max символов.',
            'blocks.array' => 'Блоки страницы должны быть объектом/массивом.',
            'is_active.boolean' => 'Поле «Активна» должно быть логическим значением.',
        ];
    }
}
