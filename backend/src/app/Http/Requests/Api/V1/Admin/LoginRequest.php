<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация входа администратора.
 */
class LoginRequest extends FormRequest
{
    /**
     * Разрешает запрос гостям.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации логина.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
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
            'email.required' => 'Укажите email.',
            'email.email' => 'Email указан неверно.',
            'password.required' => 'Укажите пароль.',
        ];
    }
}
