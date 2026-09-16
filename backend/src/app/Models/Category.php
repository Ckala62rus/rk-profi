<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Категория каталога (Перчатки, Обувь и т.д.).
 *
 * Изображение хранится в Spatie Media Library (коллекция `image`),
 * с fallback на legacy-поле `image_path` (сид/старые данные).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image_path Относительный путь в storage (legacy)
 * @property int $sort
 * @property bool $is_active
 */
class Category extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Массово заполняемые поля.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'sort',
        'is_active',
    ];

    /**
     * Атрибуты, добавляемые в JSON-сериализацию.
     *
     * @var list<string>
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * Приведения типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * Регистрирует медиа-коллекции категории.
     *
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml']);
    }

    /**
     * Товары категории.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort');
    }

    /**
     * Публичный URL изображения категории.
     *
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        /** @var Media|null $media */
        $media = $this->getFirstMedia('image');
        if ($media !== null) {
            return $media->getUrl();
        }

        if ($this->image_path === null || $this->image_path === '') {
            return null;
        }

        // Внешний URL, абсолютный путь шаблона (/template/…) или storage
        if (
            str_starts_with($this->image_path, 'http://')
            || str_starts_with($this->image_path, 'https://')
            || str_starts_with($this->image_path, '/')
        ) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
