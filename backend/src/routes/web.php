<?php

use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Api\V1\Admin\AdminCrudController;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\PublicApiController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web (Inertia)
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/services', [PublicPageController::class, 'services'])->name('services');
Route::get('/contacts', [PublicPageController::class, 'contacts'])->name('contacts');
Route::get('/privacy', [PublicPageController::class, 'privacy'])->name('privacy');
Route::get('/catalog', [PublicPageController::class, 'catalog'])->name('catalog');
Route::get('/catalog/{categorySlug}', [PublicPageController::class, 'category'])->name('catalog.category');
Route::get('/catalog/{categorySlug}/{productSlug}', [PublicPageController::class, 'product'])->name('catalog.product');

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminPageController::class, 'login'])->name('admin.login');
    Route::get('/', [AdminPageController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/categories', [AdminPageController::class, 'categories'])->name('admin.categories');
    Route::get('/products', [AdminPageController::class, 'products'])->name('admin.products');
    Route::get('/leads', [AdminPageController::class, 'leads'])->name('admin.leads');
    Route::get('/settings', [AdminPageController::class, 'settings'])->name('admin.settings');
    Route::get('/pages', [AdminPageController::class, 'pages'])->name('admin.pages');
});

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->group(function () {
    Route::get('/settings/contacts', [PublicApiController::class, 'contacts']);
    Route::get('/pages/{slug}', [PublicApiController::class, 'page']);
    Route::get('/catalog/categories', [PublicApiController::class, 'categories']);
    Route::get('/catalog/categories/{slug}/products', [PublicApiController::class, 'categoryProducts']);
    Route::get('/catalog/products/{slug}', [PublicApiController::class, 'product']);
    Route::post('/leads', [PublicApiController::class, 'storeLead'])->middleware('throttle:10,1');

    Route::prefix('admin')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);

            Route::get('/leads', [AdminCrudController::class, 'leadsIndex']);
            Route::patch('/leads/{lead}', [AdminCrudController::class, 'leadsUpdate']);
            Route::post('/leads/{lead}/retry-email', [AdminCrudController::class, 'leadsRetryEmail']);

            Route::get('/categories', [AdminCrudController::class, 'categoriesIndex']);
            Route::post('/categories', [AdminCrudController::class, 'categoriesStore']);
            Route::match(['put', 'post'], '/categories/{category}', [AdminCrudController::class, 'categoriesUpdate']);
            Route::delete('/categories/{category}', [AdminCrudController::class, 'categoriesDestroy']);

            Route::get('/products', [AdminCrudController::class, 'productsIndex']);
            Route::post('/products', [AdminCrudController::class, 'productsStore']);
            Route::match(['put', 'post'], '/products/{product}', [AdminCrudController::class, 'productsUpdate']);
            Route::delete('/products/{product}', [AdminCrudController::class, 'productsDestroy']);
            Route::delete('/products/{product}/media/{media}', [AdminCrudController::class, 'productsDestroyMedia']);

            Route::get('/pages', [AdminCrudController::class, 'pagesIndex']);
            Route::put('/pages/{page}', [AdminCrudController::class, 'pagesUpdate']);
            Route::post('/pages/{page}/images', [AdminCrudController::class, 'pagesUploadImage']);

            Route::get('/settings/contacts', [AdminCrudController::class, 'settingsContactsShow']);
            Route::put('/settings/contacts', [AdminCrudController::class, 'settingsContactsUpdate']);
        });
    });
});
