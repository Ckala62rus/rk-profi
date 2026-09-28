<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Заменяет стандартные иллюстрации сайта фотографиями из публичного каталога.
 */
return new class extends Migration
{
    /**
     * Выполняет миграцию.
     *
     * @return void
     */
    public function up(): void
    {
        $categoryImages = [
            '/template/demo/categories/cat-01.svg' => '/images/defaults/category-ppe.jpg',
            '/template/demo/categories/cat-02.svg' => '/images/defaults/category-workwear.jpg',
            '/template/demo/categories/cat-03.svg' => '/images/defaults/category-haberdashery.jpg',
            '/template/demo/categories/cat-04.svg' => '/images/defaults/category-bath.jpg',
            '/template/demo/categories/cat-05.svg' => '/images/defaults/category-shoes.jpg',
            '/template/demo/categories/cat-06.svg' => '/images/defaults/category-sheepskin.jpg',
        ];

        foreach ($categoryImages as $oldPath => $newPath) {
            DB::table('categories')
                ->where('image_path', $oldPath)
                ->update(['image_path' => $newPath]);
        }

        $page = DB::table('pages')->where('slug', 'home')->first();
        if ($page === null) {
            return;
        }

        $blocks = json_decode($page->blocks, true);
        if (! is_array($blocks)) {
            return;
        }

        $hero = $blocks['hero'] ?? [];
        if (($hero['background_video_url'] ?? null) === '/template/video.mp4') {
            $blocks['hero']['background_type'] = 'image';
            $blocks['hero']['background_image_url'] = '/images/defaults/hero.jpg';
            $blocks['hero']['background_video_url'] = null;
        }

        $about = $blocks['about'] ?? [];
        if (($about['background_image_url'] ?? null) === '/template/demo/pages/home-about.svg') {
            $blocks['about']['background_image_url'] = '/images/defaults/home-about.jpg';
        }

        DB::table('pages')
            ->where('id', $page->id)
            ->update(['blocks' => json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    /**
     * Откатывает миграцию.
     *
     * @return void
     */
    public function down(): void
    {
        $categoryImages = [
            '/images/defaults/category-ppe.jpg' => '/template/demo/categories/cat-01.svg',
            '/images/defaults/category-workwear.jpg' => '/template/demo/categories/cat-02.svg',
            '/images/defaults/category-haberdashery.jpg' => '/template/demo/categories/cat-03.svg',
            '/images/defaults/category-bath.jpg' => '/template/demo/categories/cat-04.svg',
            '/images/defaults/category-shoes.jpg' => '/template/demo/categories/cat-05.svg',
            '/images/defaults/category-sheepskin.jpg' => '/template/demo/categories/cat-06.svg',
        ];

        foreach ($categoryImages as $newPath => $oldPath) {
            DB::table('categories')
                ->where('image_path', $newPath)
                ->update(['image_path' => $oldPath]);
        }

        $page = DB::table('pages')->where('slug', 'home')->first();
        if ($page === null) {
            return;
        }

        $blocks = json_decode($page->blocks, true);
        if (! is_array($blocks)) {
            return;
        }

        if (($blocks['hero']['background_image_url'] ?? null) === '/images/defaults/hero.jpg') {
            $blocks['hero']['background_type'] = 'video';
            $blocks['hero']['background_image_url'] = null;
            $blocks['hero']['background_video_url'] = '/template/video.mp4';
        }

        if (($blocks['about']['background_image_url'] ?? null) === '/images/defaults/home-about.jpg') {
            $blocks['about']['background_image_url'] = '/template/demo/pages/home-about.svg';
        }

        DB::table('pages')
            ->where('id', $page->id)
            ->update(['blocks' => json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }
};
