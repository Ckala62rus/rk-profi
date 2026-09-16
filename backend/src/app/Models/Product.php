<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Товар каталога.
 *
 * Превью и галерея — Spatie Media Library (`thumb`, `gallery`),
 * с fallback на `thumb_path` / `product_images`.
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $sku
 * @property string|null $short_description
 * @property string|null $description
 * @property string|null $thumb_path
 * @property int $sort
 * @property bool $is_active
 */
class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Массово заполняемые поля.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'thumb_path',
        'sort',
        'is_active',
    ];

    /**
     * Атрибуты, добавляемые в JSON-сериализацию.
     *
     * @var list<string>
     */
    protected $appends = [
        'thumb_url',
        'gallery_images',
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
     * Регистрирует медиа-коллекции товара.
     *
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumb')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml']);
    }

    /**
     * Категория товара.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Изображения галереи товара (legacy-таблица).
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    /**
     * Публичный URL превью товара.
     *
     * @return string|null
     */
    public function getThumbUrlAttribute(): ?string
    {
        /** @var Media|null $media */
        $media = $this->getFirstMedia('thumb');
        if ($media !== null) {
            return $media->getUrl();
        }

        if ($this->thumb_path === null || $this->thumb_path === '') {
            return null;
        }

        if (str_starts_with($this->thumb_path, 'http://') || str_starts_with($this->thumb_path, 'https://') || str_starts_with($this->thumb_path, '/')) {
            return $this->thumb_path;
        }

        return Storage::disk('public')->url($this->thumb_path);
    }

    /**
     * Унифицированный список картинок галереи для API/Inertia.
     *
     * @return list<array{id: int|null, url: string, alt: string|null, sort: int}>
     */
    public function getGalleryImagesAttribute(): array
    {
        $mediaItems = $this->getMedia('gallery');
        if ($mediaItems->isNotEmpty()) {
            return $mediaItems
                ->values()
                ->map(fn (Media $media, int $index) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'alt' => $media->name,
                    'sort' => $media->order_column ?? $index,
                ])
                ->all();
        }

        /** @var Collection<int, ProductImage> $legacy */
        $legacy = $this->relationLoaded('images')
            ? $this->images
            : $this->images()->get();

        return $legacy
            ->values()
            ->map(fn (ProductImage $image, int $index) => [
                'id' => $image->id,
                'url' => $image->url,
                'alt' => $image->alt,
                'sort' => $image->sort ?? $index,
            ])
            ->all();
    }
}
