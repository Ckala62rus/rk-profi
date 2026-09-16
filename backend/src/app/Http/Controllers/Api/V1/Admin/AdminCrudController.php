<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\SyncCategoryImageAction;
use App\Actions\SyncProductMediaAction;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\StoreProductRequest;
use App\Http\Requests\Api\V1\Admin\UpdateContactsSettingsRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePageRequest;
use App\Http\Requests\Api\V1\Admin\UploadPageMediaRequest;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\Page;
use App\Models\Product;
use App\Services\SiteSettingsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * CRUD-эндпоинты админки (категории, товары, страницы, заявки, настройки).
 */
class AdminCrudController extends Controller
{
    /**
     * Список заявок.
     *
     * @return JsonResponse
     */
    public function leadsIndex(): JsonResponse
    {
        $leads = LeadRequest::query()->latest()->paginate(20);

        return ApiResponse::success($leads);
    }

    /**
     * Обновление статуса заявки.
     *
     * @param Request $request HTTP-запрос
     * @param LeadRequest $lead Заявка
     * @return JsonResponse
     */
    public function leadsUpdate(Request $request, LeadRequest $lead): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(LeadStatus::class)],
        ], [
            'status.required' => 'Укажите статус заявки.',
        ]);

        $lead->update(['status' => $data['status']]);

        return ApiResponse::success($lead->fresh());
    }

    /**
     * Список категорий (включая неактивные).
     *
     * @return JsonResponse
     */
    public function categoriesIndex(): JsonResponse
    {
        return ApiResponse::success(
            Category::query()->with('media')->orderBy('sort')->get()
        );
    }

    /**
     * Создание категории (опционально с изображением).
     *
     * @param StoreCategoryRequest $request Валидированный запрос
     * @param SyncCategoryImageAction $syncImage Синхронизация изображения
     * @return JsonResponse
     */
    public function categoriesStore(
        StoreCategoryRequest $request,
        SyncCategoryImageAction $syncImage
    ): JsonResponse {
        $data = $request->safe()->except(['image']);
        $data['slug'] = ($data['slug'] ?? '') !== '' ? $data['slug'] : Str::slug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort'] = $data['sort'] ?? 0;

        $category = Category::query()->create($data);

        if ($request->hasFile('image')) {
            $syncImage->execute($category, $request->file('image'));
        }

        return ApiResponse::success($category->fresh('media'), null, 201);
    }

    /**
     * Обновление категории (опционально с новым изображением).
     *
     * @param StoreCategoryRequest $request Валидированный запрос
     * @param Category $category Категория
     * @param SyncCategoryImageAction $syncImage Синхронизация изображения
     * @return JsonResponse
     */
    public function categoriesUpdate(
        StoreCategoryRequest $request,
        Category $category,
        SyncCategoryImageAction $syncImage
    ): JsonResponse {
        $data = $request->safe()->except(['image']);
        if (array_key_exists('slug', $data) && ($data['slug'] ?? '') === '') {
            unset($data['slug']);
        }

        $category->update($data);

        if ($request->hasFile('image')) {
            $syncImage->execute($category, $request->file('image'));
        }

        return ApiResponse::success($category->fresh('media'));
    }

    /**
     * Удаление категории.
     *
     * @param Category $category Категория
     * @return JsonResponse
     */
    public function categoriesDestroy(Category $category): JsonResponse
    {
        $category->clearMediaCollection('image');
        $category->delete();

        return ApiResponse::success(null, 'Категория удалена.');
    }

    /**
     * Список товаров.
     *
     * @return JsonResponse
     */
    public function productsIndex(): JsonResponse
    {
        return ApiResponse::success(
            Product::query()->with(['category', 'images', 'media'])->orderBy('sort')->paginate(30)
        );
    }

    /**
     * Создание товара (опционально с превью и галереей).
     *
     * @param StoreProductRequest $request Валидированный запрос
     * @param SyncProductMediaAction $syncMedia Синхронизация медиа
     * @return JsonResponse
     */
    public function productsStore(
        StoreProductRequest $request,
        SyncProductMediaAction $syncMedia
    ): JsonResponse {
        $data = $request->safe()->except(['thumb', 'gallery']);
        $data['slug'] = ($data['slug'] ?? '') !== '' ? $data['slug'] : Str::slug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort'] = $data['sort'] ?? 0;

        $product = Product::query()->create($data);

        $gallery = $request->file('gallery', []);
        if (! is_array($gallery)) {
            $gallery = $gallery ? [$gallery] : [];
        }

        if ($request->hasFile('thumb') || $gallery !== []) {
            $syncMedia->execute($product, $request->file('thumb'), $gallery);
        }

        return ApiResponse::success(
            $product->fresh(['category', 'images', 'media']),
            null,
            201
        );
    }

    /**
     * Обновление товара (опционально с превью и галереей).
     *
     * @param StoreProductRequest $request Валидированный запрос
     * @param Product $product Товар
     * @param SyncProductMediaAction $syncMedia Синхронизация медиа
     * @return JsonResponse
     */
    public function productsUpdate(
        StoreProductRequest $request,
        Product $product,
        SyncProductMediaAction $syncMedia
    ): JsonResponse {
        $data = $request->safe()->except(['thumb', 'gallery']);
        if (array_key_exists('slug', $data) && ($data['slug'] ?? '') === '') {
            unset($data['slug']);
        }

        $product->update($data);

        $gallery = $request->file('gallery', []);
        if (! is_array($gallery)) {
            $gallery = $gallery ? [$gallery] : [];
        }

        if ($request->hasFile('thumb') || $gallery !== []) {
            $syncMedia->execute($product, $request->file('thumb'), $gallery);
        }

        return ApiResponse::success($product->fresh(['category', 'images', 'media']));
    }

    /**
     * Удаление товара.
     *
     * @param Product $product Товар
     * @return JsonResponse
     */
    public function productsDestroy(Product $product): JsonResponse
    {
        $product->clearMediaCollection('thumb');
        $product->clearMediaCollection('gallery');
        $product->delete();

        return ApiResponse::success(null, 'Товар удалён.');
    }

    /**
     * Удаление одного файла галереи товара (Spatie Media).
     *
     * @param Product $product Товар-владелец
     * @param Media $media Медиафайл
     * @return JsonResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function productsDestroyMedia(Product $product, Media $media): JsonResponse
    {
        if ((int) $media->model_id !== (int) $product->id
            || $media->model_type !== Product::class
            || $media->collection_name !== 'gallery'
        ) {
            abort(404, 'Файл галереи не найден.');
        }

        $media->delete();

        return ApiResponse::success(
            $product->fresh(['category', 'images', 'media']),
            'Файл галереи удалён.'
        );
    }

    /**
     * Список контентных страниц.
     *
     * @return JsonResponse
     */
    public function pagesIndex(): JsonResponse
    {
        return ApiResponse::success(Page::query()->orderBy('slug')->get());
    }

    /**
     * Обновление страницы.
     *
     * @param UpdatePageRequest $request Валидированный запрос
     * @param Page $page Страница
     * @return JsonResponse
     */
    public function pagesUpdate(UpdatePageRequest $request, Page $page): JsonResponse
    {
        $page->update($request->validated());

        return ApiResponse::success($page->fresh());
    }

    /**
     * Загрузка изображения/видео для CMS-страницы (публичный URL).
     *
     * @param UploadPageMediaRequest $request Валидированный файл
     * @param Page $page Страница
     * @return JsonResponse
     */
    public function pagesUploadImage(UploadPageMediaRequest $request, Page $page): JsonResponse
    {
        $file = $request->file('file');
        $kind = (string) $request->input('kind', 'image');
        $field = (string) $request->input('field', $kind);
        $safeField = preg_replace('/[^a-z0-9_-]+/i', '-', $field) ?: $kind;

        $path = $file->storeAs(
            'pages/'.$page->slug,
            $safeField.'-'.Str::random(8).'.'.$file->getClientOriginalExtension(),
            'public'
        );

        return ApiResponse::success([
            // Относительный URL — работает с любым host/портом (8090 и т.д.)
            'url' => '/storage/'.$path,
            'path' => $path,
            'field' => $safeField,
            'kind' => $kind,
        ], $kind === 'video' ? 'Видео загружено.' : 'Изображение загружено.');
    }

    /**
     * Получение контактов для редактирования.
     *
     * @param SiteSettingsService $settings Сервис настроек
     * @return JsonResponse
     */
    public function settingsContactsShow(SiteSettingsService $settings): JsonResponse
    {
        return ApiResponse::success($settings->contacts());
    }

    /**
     * Сохранение контактов.
     *
     * @param UpdateContactsSettingsRequest $request Валидированный запрос
     * @param SiteSettingsService $settings Сервис настроек
     * @return JsonResponse
     */
    public function settingsContactsUpdate(
        UpdateContactsSettingsRequest $request,
        SiteSettingsService $settings
    ): JsonResponse {
        $settings->putContacts(array_merge($settings->contacts(), $request->validated()));

        return ApiResponse::success($settings->contacts());
    }
}
