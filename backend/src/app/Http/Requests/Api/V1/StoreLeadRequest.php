<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\TurnstileToken;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация публичной формы заявки.
 */
class StoreLeadRequest extends FormRequest
{
    /**
     * Разрешает запрос всем гостям.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации полей формы.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'min:10', 'max:40'],
            'email' => ['required', 'email', 'max:190'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Согласие на обработку персональных данных (ПДн)
            'privacy_accepted' => ['accepted'],
            'attachment' => ['sometimes', 'array'],
            'attachment.*' => ['file', 'max:10240'],
            'company_card' => ['sometimes', 'array'],
            'company_card.*' => ['file', 'max:10240'],
            // Honeypot: проверяется в контроллере, здесь только принимаем строку
            'company_site' => ['nullable', 'string'],
            // Timestamp открытия формы (мс) — защита от слишком быстрой отправки ботом
            'form_opened_at' => ['nullable', 'integer'],
            // Cloudflare Turnstile (обязателен только если задан TURNSTILE_SECRET_KEY)
            'cf_turnstile_response' => ['nullable', 'string', new TurnstileToken],
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
            'name.required' => 'Укажите ваше имя.',
            'name.min' => 'Имя должно содержать не менее 2 символов.',
            'name.string' => 'Имя должно быть строкой.',
            'name.max' => 'Имя не должно превышать :max символов.',
            'phone.required' => 'Укажите телефон.',
            'phone.min' => 'Телефон указан некорректно.',
            'phone.string' => 'Телефон должен быть строкой.',
            'phone.max' => 'Телефон не должен превышать :max символов.',
            'email.required' => 'Укажите email.',
            'email.email' => 'Email указан неверно.',
            'email.max' => 'Email не должен превышать :max символов.',
            'message.required' => 'Опишите заявку.',
            'message.min' => 'Описание заявки слишком короткое.',
            'message.string' => 'Описание заявки должно быть строкой.',
            'message.max' => 'Описание заявки не должно превышать :max символов.',
            'privacy_accepted.accepted' => 'Необходимо согласие на обработку персональных данных.',
            'attachment.array' => 'Вложения передаются списком файлов.',
            'attachment.*.file' => 'Каждое вложение должно быть файлом.',
            'attachment.*.max' => 'Файл вложения не должен превышать 10 МБ.',
            'company_card.array' => 'Карточка предприятия передаётся списком файлов.',
            'company_card.*.file' => 'Карточка предприятия должна быть файлом.',
            'company_card.*.max' => 'Файл карточки предприятия не должен превышать 10 МБ.',
            'company_site.string' => 'Некорректное значение служебного поля.',
            'form_opened_at.integer' => 'Некорректная метка времени формы.',
            'cf_turnstile_response.string' => 'Токен проверки антиспама должен быть строкой.',
        ];
    }

    /**
     * Доп. проверка: слишком быстрая отправка (менее 3 сек) — похоже на бота.
     *
     * @param \Illuminate\Validation\Validator $validator Валидатор
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $openedAt = $this->input('form_opened_at');
            if ($openedAt === null || $openedAt === '') {
                return;
            }

            $openedMs = (int) $openedAt;
            $elapsedMs = (int) (microtime(true) * 1000) - $openedMs;

            // Меньше 2 сек — типичный бот; людям обычно нужно больше на заполнение
            if ($elapsedMs < 2000) {
                $validator->errors()->add(
                    'form_opened_at',
                    'Пожалуйста, заполните форму чуть внимательнее и отправьте снова.'
                );
            }
        });
    }
}
