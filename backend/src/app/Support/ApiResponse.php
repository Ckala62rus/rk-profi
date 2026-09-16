<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Единый JSON-конверт ответов API РК ПРОФИ.
 */
class ApiResponse
{
    /**
     * Успешный ответ.
     *
     * @param mixed $data Полезная нагрузка
     * @param string|null $message Опциональное сообщение
     * @param int $status HTTP-код
     * @return JsonResponse
     */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    /**
     * Ответ об ошибке.
     *
     * @param string $message Текст ошибки
     * @param array<string, mixed> $errors Ошибки валидации по полям
     * @param int $status HTTP-код
     * @return JsonResponse
     */
    public static function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
