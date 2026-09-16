<?php

namespace App\Actions;

use App\Models\Product;
use Illuminate\Http\UploadedFile;

/**
 * Сохраняет превью и/или галерею товара в Spatie Media Library.
 */
class SyncProductMediaAction
{
    /**
     * Синхронизирует медиа товара.
     *
     * @param Product $product Товар
     * @param UploadedFile|null $thumb Превью (коллекция `thumb`)
     * @param array<int, UploadedFile> $galleryFiles Новые файлы галереи
     * @return Product
     */
    public function execute(Product $product, ?UploadedFile $thumb = null, array $galleryFiles = []): Product
    {
        if ($thumb instanceof UploadedFile) {
            $product
                ->addMedia($thumb)
                ->usingFileName($this->safeFileName($thumb))
                ->toMediaCollection('thumb');
        }

        foreach ($galleryFiles as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $product
                ->addMedia($file)
                ->usingFileName($this->safeFileName($file))
                ->toMediaCollection('gallery');
        }

        return $product->fresh(['category', 'images', 'media']);
    }

    /**
     * Безопасное имя файла для хранения.
     *
     * @param UploadedFile $file Файл
     * @return string
     */
    private function safeFileName(UploadedFile $file): string
    {
        $original = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $base = pathinfo($original, PATHINFO_FILENAME) ?: 'image';

        return preg_replace('/[^a-zA-Z0-9._-]+/', '-', $base).'.'.$extension;
    }
}
