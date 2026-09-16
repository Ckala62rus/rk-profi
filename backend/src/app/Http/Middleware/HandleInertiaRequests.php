<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Shared props Inertia: контакты и главное меню на каждой странице.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * Корневой Blade по умолчанию (публичка).
     *
     * @var string
     */
    protected $rootView = 'public';

    /**
     * Разные корневые шаблоны: сайт и админка не делят CSS/JS.
     *
     * @param Request $request HTTP-запрос
     * @return string
     */
    public function rootView(Request $request): string
    {
        return $request->is('admin', 'admin/*') ? 'admin' : 'public';
    }

    /**
     * Версия ассетов для cache busting.
     *
     * @param Request $request HTTP-запрос
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Общие props для всех Inertia-страниц.
     *
     * @param Request $request HTTP-запрос
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var SiteSettingsService $settings */
        $settings = app(SiteSettingsService::class);

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'contacts' => fn () => $settings->contacts(),
            'mainMenu' => fn () => $settings->mainMenu(),
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Cloudflare Turnstile: site key для виджета (пустой = капча выключена)
            'turnstileSiteKey' => config('services.turnstile.site_key') ?: null,
        ];
    }
}
