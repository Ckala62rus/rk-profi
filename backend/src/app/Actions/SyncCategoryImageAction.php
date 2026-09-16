<?php

namespace App\Actions;

use App\Models\Category;
use Illuminate\Http\UploadedFile;

/**
 * Сохраняет/заменяет изображение категории в Spatie Media Library.
 */
class SyncCategoryImageAction
{
    /**
     * Прикрепляет файл к коллекции `image` (singleFile — старый заменяется).
     *
     * @param Category $category Категория
     * @param UploadedFile $file Загруженный файл изображения
     * @return Category
     */
    public function execute(Category $category, UploadedFile $file): Category
    {
        $category
            ->addMedia($file)
            ->usingFileName($this->safeFileName($file))
            ->toMediaCollection('image');

        return $category->fresh();
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

        return pathinfo($original, PATHINFO_FILENAME)
            ? preg_replace('/[^a-zA-Z0-9._-]+/', '-', pathinfo($original, PATHINFO_FILENAME)).'.'.$extension
            : 'image.'.$extension;
    }
}
