<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Создание/обновление категории (поля + опциональное изображение).
 */
class StoreCategoryRequest extends FormRequest
{
    /**
     * Разрешает запрос авторизованному админу (auth:sanctum на маршруте).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Нормализует boolean из FormData (строки "true"/"false"/"1"/"0").
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->exists('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Правила валидации.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => [$categoryId ? 'sometimes' : 'required', 'string', 'max:190'],
            'slug' => [
                'nullable',
                'string',
                'max:190',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /**
     * Русские сообщения валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Укажите название категории.',
            'name.string' => 'Название категории должно быть строкой.',
            'name.max' => 'Название категории не должно превышать :max символов.',
            'slug.string' => 'Слаг должен быть строкой.',
            'slug.max' => 'Слаг не должен превышать :max символов.',
            'slug.unique' => 'Такой слаг уже занят.',
            'description.string' => 'Описание должно быть строкой.',
            'sort.integer' => 'Сортировка должна быть целым числом.',
            'sort.min' => 'Сортировка не может быть меньше :min.',
            'is_active.boolean' => 'Признак активности должен быть логическим значением.',
            'image.image' => 'Файл изображения категории должен быть картинкой.',
            'image.max' => 'Изображение категории не должно превышать 5 МБ.',
        ];
    }
}
