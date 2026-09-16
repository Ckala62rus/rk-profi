<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateLeadRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLeadRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\PageResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Services\SiteSettingsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Публичное REST API витрины (/api/v1).
 */
class PublicApiController extends Controller
{
    /**
     * Контакты для шапки/футера.
     *
     * @param SiteSettingsService $settings Сервис настроек
     * @return JsonResponse
     */
    public function contacts(SiteSettingsService $settings): JsonResponse
    {
        return ApiResponse::success($settings->contacts());
    }

    /**
     * Контент страницы по слагу.
     *
     * @param string $slug Слаг страницы (home, about, …)
     * @return JsonResponse
     */
    public function page(string $slug): JsonResponse
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return ApiResponse::success(new PageResource($page));
    }

    /**
     * Список активных категорий каталога.
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $items = Category::query()
            ->where('is_active', true)
            ->with('media')
            ->orderBy('sort')
            ->get();

        return ApiResponse::success(CategoryResource::collection($items));
    }

    /**
     * Товары категории по слагу.
     *
     * @param string $slug Слаг категории
     * @return JsonResponse
     */
    public function categoryProducts(string $slug): JsonResponse
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('media')
            ->firstOrFail();

        $products = $category->products()
            ->where('is_active', true)
            ->with(['images', 'media'])
            ->get();

        return ApiResponse::success([
            'category' => new CategoryResource($category),
            'products' => ProductResource::collection($products),
        ]);
    }

    /**
     * Карточка товара по слагу.
     *
     * @param string $slug Слаг товара
     * @return JsonResponse
     */
    public function product(string $slug): JsonResponse
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['images', 'category', 'media'])
            ->firstOrFail();

        return ApiResponse::success(new ProductResource($product));
    }

    /**
     * Приём заявки с формы.
     *
     * @param StoreLeadRequest $request Валидированный запрос
     * @param CreateLeadRequestAction $action Action создания заявки
     * @return JsonResponse
     */
    public function storeLead(StoreLeadRequest $request, CreateLeadRequestAction $action): JsonResponse
    {
        // Honeypot: боты заполняют скрытое поле — притворяемся успехом
        if (filled($request->input('company_site'))) {
            return ApiResponse::success(['id' => 0, 'status' => 'new'], null, 201);
        }

        $lead = $action->execute(
            [
                'name' => $request->string('name')->toString(),
                'phone' => $request->string('phone')->toString(),
                'email' => $request->string('email')->toString(),
                'message' => $request->string('message')->toString(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            $this->normalizeUploadedFiles($request->file('attachment')),
            $this->normalizeUploadedFiles($request->file('company_card'))
        );

        return ApiResponse::success([
            'id' => $lead->id,
            'status' => $lead->status->value,
        ], null, 201);
    }

    /**
     * Приводит input file к списку UploadedFile (один файл или массив).
     *
     * @param mixed $files Значение $request->file(...)
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    private function normalizeUploadedFiles(mixed $files): array
    {
        if ($files instanceof \Illuminate\Http\UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(
            $files,
            static fn ($file) => $file instanceof \Illuminate\Http\UploadedFile
        ));
    }
}
