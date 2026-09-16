<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Сервис чтения/записи настроек сайта (контакты, логотип и т.д.).
 */
class SiteSettingsService
{
    /** Ключ кэша контактов для публичного фронта */
    private const CACHE_CONTACTS = 'site_settings.contacts';

    /**
     * Возвращает контакты для шапки, футера и страницы «Контакты».
     *
     * @return array<string, mixed>
     */
    public function contacts(): array
    {
        return Cache::remember(self::CACHE_CONTACTS, 3600, function () {
            $setting = SiteSetting::query()->where('key', 'contacts')->first();
            $defaults = $this->defaultContacts();
            $stored = is_array($setting?->value) ? $setting->value : [];
            $merged = array_replace($defaults, $stored);

            // Если в БД ещё нет карты — подставляем дефолтный iframe Яндекса
            if (empty($merged['map_embed_url'])) {
                $merged['map_embed_url'] = $defaults['map_embed_url'];
            }

            return $merged;
        });
    }

    /**
     * Данные контактов по умолчанию (РК ПРОФИ).
     *
     * @return array<string, mixed>
     */
    public function defaultContacts(): array
    {
        return [
            'phones' => [
                [
                    'label' => 'Основной',
                    'display' => '8-4912-30-65-80',
                    'tel' => '+74912306580',
                ],
            ],
            'emails' => [
                [
                    'label' => 'Отдел продаж',
                    'value' => 'goods@leather.ru',
                ],
            ],
            'addresses' => [
                [
                    'label' => 'Адрес',
                    'value' => '390028, г. Рязань ул. Прижелезнодорожная, 52.',
                ],
            ],
            'map_embed_url' => 'https://yandex.ru/map-widget/v1/?um=constructor%3A2897b4396ef3155a930d31858de1eda5e1660e0ab60a1d9e435cb3ca3746df61&source=constructor',
            'office_photo_url' => null,
            'copyright' => '© РК ПРОФИ',
        ];
    }

    /**
     * Сохраняет блок контактов и сбрасывает кэш.
     *
     * @param array<string, mixed> $value
     * @return void
     */
    public function putContacts(array $value): void
    {
        SiteSetting::query()->updateOrCreate(
            ['key' => 'contacts'],
            ['value' => $value]
        );

        Cache::forget(self::CACHE_CONTACTS);
    }

    /**
     * Пункты главного меню для шапки/футера.
     *
     * @return list<array{label: string, href: string}>
     */
    public function mainMenu(): array
    {
        return [
            ['label' => 'Главная', 'href' => '/'],
            ['label' => 'О компании', 'href' => '/about'],
            ['label' => 'Каталог', 'href' => '/catalog'],
            ['label' => 'Услуги', 'href' => '/services'],
            ['label' => 'Контакты', 'href' => '/contacts'],
        ];
    }
}
