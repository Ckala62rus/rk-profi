<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Создание/обновление товара (поля + превью + галерея).
 */
class StoreProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id;

        return [
            'category_id' => [$productId ? 'sometimes' : 'required', 'exists:categories,id'],
            'name' => [$productId ? 'sometimes' : 'required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'sku' => ['nullable', 'string', 'max:100'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'thumb' => ['nullable', 'image', 'max:5120'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'max:5120'],
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
            'category_id.required' => 'Выберите категорию.',
            'category_id.exists' => 'Выбранная категория не найдена.',
            'name.required' => 'Укажите название товара.',
            'name.string' => 'Название товара должно быть строкой.',
            'name.max' => 'Название товара не должно превышать :max символов.',
            'slug.string' => 'Слаг должен быть строкой.',
            'slug.max' => 'Слаг не должен превышать :max символов.',
            'sku.string' => 'Артикул должен быть строкой.',
            'sku.max' => 'Артикул не должен превышать :max символов.',
            'short_description.string' => 'Краткое описание должно быть строкой.',
            'short_description.max' => 'Краткое описание не должно превышать :max символов.',
            'description.string' => 'Описание должно быть строкой.',
            'sort.integer' => 'Сортировка должна быть целым числом.',
            'sort.min' => 'Сортировка не может быть меньше :min.',
            'is_active.boolean' => 'Признак активности должен быть логическим значением.',
            'thumb.image' => 'Превью товара должно быть изображением.',
            'thumb.max' => 'Превью товара не должно превышать 5 МБ.',
            'gallery.array' => 'Галерея должна быть списком файлов.',
            'gallery.*.image' => 'Каждый файл галереи должен быть изображением.',
            'gallery.*.max' => 'Файл галереи не должен превышать 5 МБ.',
        ];
    }
}
