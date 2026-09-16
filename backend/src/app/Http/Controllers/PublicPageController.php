<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Services\SiteSettingsService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inertia-контроллеры публичных страниц сайта.
 */
class PublicPageController extends Controller
{
    /**
     * Главная страница.
     *
     * @return Response
     */
    public function home(): Response
    {
        $page = Page::query()->where('slug', 'home')->where('is_active', true)->first();
        $categories = Category::query()->where('is_active', true)->orderBy('sort')->get();

        return Inertia::render('Public/Home', [
            'page' => $page,
            'categories' => $categories->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'image_url' => $c->image_url,
            ]),
        ]);
    }

    /**
     * Страница «О компании».
     *
     * @return Response
     */
    public function about(): Response
    {
        $page = Page::query()->where('slug', 'about')->where('is_active', true)->firstOrFail();

        return Inertia::render('Public/About', [
            'page' => [
                'title' => $page->title,
                'blocks' => $page->blocks,
            ],
        ]);
    }

    /**
     * Страница «Услуги».
     *
     * @return Response
     */
    public function services(): Response
    {
        $page = Page::query()->where('slug', 'services')->where('is_active', true)->firstOrFail();

        return Inertia::render('Public/Services', [
            'page' => [
                'title' => $page->title,
                'blocks' => $page->blocks,
            ],
        ]);
    }

    /**
     * Страница «Контакты».
     *
     * @param SiteSettingsService $settings Сервис настроек
     * @return Response
     */
    public function contacts(SiteSettingsService $settings): Response
    {
        $page = Page::query()->where('slug', 'contacts')->where('is_active', true)->first();

        return Inertia::render('Public/Contacts', [
            'page' => $page ? [
                'title' => $page->title,
                'blocks' => $page->blocks,
            ] : null,
            'contactsDetail' => $settings->contacts(),
        ]);
    }

    /**
     * Политика конфиденциальности (ПДн).
     *
     * @return Response
     */
    public function privacy(): Response
    {
        $page = Page::query()->where('slug', 'privacy')->where('is_active', true)->first();

        return Inertia::render('Public/Privacy', [
            'page' => $page ? [
                'title' => $page->title,
                'blocks' => $page->blocks,
            ] : [
                'title' => 'Политика конфиденциальности',
                'blocks' => [
                    'paragraphs' => [
                        'Настоящая политика конфиденциальности определяет порядок обработки и защиты персональных данных пользователей сайта РК ПРОФИ.',
                        'Оставляя заявку на сайте, вы соглашаетесь на обработку указанных персональных данных в целях связи по заявке.',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Список категорий каталога.
     *
     * @return Response
     */
    public function catalog(): Response
    {
        $categories = Category::query()->where('is_active', true)->orderBy('sort')->get();

        return Inertia::render('Public/Catalog', [
            'categories' => $categories->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'image_url' => $c->image_url,
            ]),
        ]);
    }

    /**
     * Товары выбранной категории.
     *
     * @param string $categorySlug Слаг категории
     * @return Response
     */
    public function category(string $categorySlug): Response
    {
        $category = Category::query()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->firstOrFail();

        $products = $category->products()->where('is_active', true)->with(['images', 'media'])->get();

        return Inertia::render('Public/Category', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'short_description' => $p->short_description,
                'thumb_url' => $p->thumb_url,
                // Для CSS-карусели на карточке категории
                'gallery_images' => $p->gallery_images,
            ]),
        ]);
    }

    /**
     * Карточка товара.
     *
     * @param string $categorySlug Слаг категории (для хлебных крошек)
     * @param string $productSlug Слаг товара
     * @return Response
     */
    public function product(string $categorySlug, string $productSlug): Response
    {
        $category = Category::query()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->firstOrFail();

        $product = Product::query()
            ->where('category_id', $category->id)
            ->where('slug', $productSlug)
            ->where('is_active', true)
            ->with(['images', 'media'])
            ->firstOrFail();

        return Inertia::render('Public/Product', [
            'category' => [
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'thumb_url' => $product->thumb_url,
                'images' => $product->gallery_images,
            ],
        ]);
    }
}
