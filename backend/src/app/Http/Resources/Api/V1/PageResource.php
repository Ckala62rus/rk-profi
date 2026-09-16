<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс контентной страницы для публичного API.
 *
 * @mixin \App\Models\Page
 */
class PageResource extends JsonResource
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
            'slug' => $this->slug,
            'title' => $this->title,
            'blocks' => $this->blocks,
        ];
    }
}
