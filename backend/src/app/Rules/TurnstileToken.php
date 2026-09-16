<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * Проверка токена Cloudflare Turnstile (если секрет задан в config).
 *
 * Если TURNSTILE_SECRET_KEY пуст — правило пропускает значение (капча выключена).
 */
class TurnstileToken implements ValidationRule
{
    /**
     * Валидирует ответ виджета Turnstile через siteverify API.
     *
     * @param string $attribute Имя поля
     * @param mixed $value Значение токена
     * @param Closure(string, ?string=): void $fail Колбэк ошибки
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');

        // Капча не настроена — не блокируем заявки на локалке.
        if (! is_string($secret) || $secret === '') {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Подтвердите, что вы не робот.');

            return;
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secret,
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (! $response->ok() || ! ($response->json('success') === true)) {
            $fail('Проверка антиспама не пройдена. Обновите страницу и попробуйте снова.');
        }
    }
}
