<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс категории каталога.
 *
 * @mixin \App\Models\Category
 */
class CategoryResource extends JsonResource
{
    /**
     * Преобразует модель в массив ответа.
     *
     * @param Request $request HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'sort' => $this->sort,
        ];
    }
}
