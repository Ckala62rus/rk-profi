<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Начальные данные: админ, контакты РК ПРОФИ, страницы и демо-каталог.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Заполняет БД стартовыми данными.
     *
     * @return void
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@rkprofi.local'],
            [
                'name' => 'Администратор',
                'password' => Hash::make('password'),
            ]
        );

        /** @var SiteSettingsService $settings */
        $settings = app(SiteSettingsService::class);
        $settings->putContacts($settings->defaultContacts());

        Page::query()->updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Главная',
                'is_active' => true,
                'blocks' => [
                    'hero' => [
                        'title' => 'Производственная компания РК ПРОФИ',
                        'subtitle' => 'Мы предлагаем широкий ассортимент изделий из натурального кожевенного спилка и натуральной овчины',
                        'background_type' => 'image',
                        'background_image_url' => '/images/defaults/hero.jpg',
                        'background_video_url' => null,
                    ],
                    'about' => [
                        'title' => 'РК ПРОФИ',
                        'background_image_url' => '/images/defaults/home-about.jpg',
                        'items' => [
                            ['title' => 'Более 20 лет на рынке', 'text' => 'Информация о предприятии.'],
                            ['title' => 'Широкий ассортимент', 'text' => 'Изделия из натурального кожевенного спилка и натуральной овчины для разных задач.'],
                            ['title' => 'Собственное производство', 'text' => 'Производственная база позволяет выпускать продукцию стабильного качества.'],
                            ['title' => 'Работа с заказчиками', 'text' => 'Подбираем изделия под требования клиента и помогаем оформить заявку.'],
                        ],
                    ],
                ],
            ]
        );

        Page::query()->updateOrCreate(
            ['slug' => 'about'],
            [
                'title' => 'О компании',
                'is_active' => true,
                'blocks' => [
                    'paragraphs' => [
                        'Мы производим и поставляем продукцию для промышленности, строительства и торговли. Компания работает с оптовыми и корпоративными клиентами, соблюдает сроки и требования к качеству.',
                        'На этой странице можно разместить ваш индивидуальный текст: историю компании, преимущества, производственные мощности, сертификаты и условия сотрудничества.',
                    ],
                ],
            ]
        );

        Page::query()->updateOrCreate(
            ['slug' => 'services'],
            [
                'title' => 'Наши услуги',
                'is_active' => true,
                'blocks' => [
                    'intro' => 'Наряду с производством изделий из натурального кожевенного спилка и овчины, РК ПРОФИ выполняет заказы по индивидуальному пошиву и комплектации партий под требования заказчика.',
                    'items' => [],
                ],
            ]
        );

        Page::query()->updateOrCreate(
            ['slug' => 'contacts'],
            [
                'title' => 'Контакты',
                'is_active' => true,
                'blocks' => [],
            ]
        );

        Page::query()->updateOrCreate(
            ['slug' => 'privacy'],
            [
                'title' => 'Политика конфиденциальности',
                'is_active' => true,
                'blocks' => [
                    'paragraphs' => [
                        'Настоящая политика конфиденциальности определяет порядок обработки и защиты персональных данных пользователей сайта РК ПРОФИ.',
                        'Оставляя заявку на сайте, вы соглашаетесь на обработку указанных персональных данных (имя, телефон, email и содержание обращения) в целях связи по заявке и исполнения договорённостей.',
                        'Данные не передаются третьим лицам, за исключением случаев, предусмотренных законодательством Российской Федерации, или когда это необходимо для исполнения заявки (например, доставка корреспонденции).',
                        'По вопросам обработки персональных данных вы можете связаться с нами по контактам, указанным на сайте.',
                    ],
                ],
            ]
        );

        $gloves = Category::query()->updateOrCreate(
            ['slug' => 'perchatki'],
            [
                'name' => 'Перчатки',
                'description' => 'Каталог моделей рабочих перчаток',
                'image_path' => '/images/defaults/category-ppe.jpg',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        Category::query()->updateOrCreate(
            ['slug' => 'spetsodezhda'],
            [
                'name' => 'Спецодежда и аксессуары',
                'description' => 'описание',
                'image_path' => '/images/defaults/category-workwear.jpg',
                'sort' => 2,
                'is_active' => true,
            ]
        );

        foreach ([
            ['slug' => 'galantereya', 'name' => 'Галантерея', 'description' => 'Изделия и аксессуары из натуральных материалов для повседневного использования.', 'image' => '/images/defaults/category-haberdashery.jpg', 'sort' => 3],
            ['slug' => 'bannye-izdeliya', 'name' => 'Банные изделия', 'description' => 'Практичные изделия для бани и сауны из натуральной овчины и кожевенного спилка.', 'image' => '/images/defaults/category-bath.jpg', 'sort' => 4],
            ['slug' => 'obuv', 'name' => 'Обувь', 'description' => 'Тёплая и надёжная обувь для работы, отдыха и повседневной носки.', 'image' => '/images/defaults/category-shoes.jpg', 'sort' => 5],
            ['slug' => 'naturalnyy-mekh', 'name' => 'Натуральный мех', 'description' => 'Материалы и готовые изделия из натурального меха для дома, отдыха и производства.', 'image' => '/images/defaults/category-sheepskin.jpg', 'sort' => 6],
        ] as $cat) {
            Category::query()->updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'image_path' => $cat['image'],
                    'sort' => $cat['sort'],
                    'is_active' => true,
                ]
            );
        }

        Product::query()->updateOrCreate(
            [
                'category_id' => $gloves->id,
                'slug' => 'perchatki-hb-pvh',
            ],
            [
                'name' => 'Перчатки ХБ с ПВХ',
                'short_description' => 'Хлопчатобумажное полотно с рельефным ПВХ — для стройки и склада.',
                'description' => 'Рабочие перчатки ХБ с ПВХ-покрытием.',
                'thumb_path' => '/template/demo/gloves/glove-01.svg',
                'sort' => 1,
                'is_active' => true,
            ]
        );

        foreach ([
            ['slug' => 'nitrilovye-perchatki', 'name' => 'Нитриловые перчатки', 'desc' => 'Одноразовые и многоразовые, химстойкие.', 'img' => '/template/demo/gloves/glove-02.svg', 'sort' => 2],
            ['slug' => 'kragi-svarshchika', 'name' => 'Краги сварщика', 'desc' => 'Спилок, длинная манжета, термозащита.', 'img' => '/template/demo/gloves/glove-03.svg', 'sort' => 3],
            ['slug' => 'perchatki-zimnie', 'name' => 'Перчатки зимние комбинированные', 'desc' => 'Утеплитель, влагостойкая подкладка.', 'img' => '/template/demo/gloves/glove-04.svg', 'sort' => 4],
            ['slug' => 'perchatki-pvh-tochka', 'name' => 'Перчатки с ПВХ-точкой', 'desc' => 'Усиленный захват, износостойкость.', 'img' => '/template/demo/gloves/glove-05.svg', 'sort' => 5],
            ['slug' => 'perchatki-mbs', 'name' => 'Перчатки МБС', 'desc' => 'Маслобензостойкие для автосервиса и производства.', 'img' => '/template/demo/gloves/glove-06.svg', 'sort' => 6],
            ['slug' => 'perchatki-latex', 'name' => 'Перчатки латексные', 'desc' => 'Эластичные, для хозяйственных и лёгких работ.', 'img' => '/template/demo/gloves/glove-07.svg', 'sort' => 7],
            ['slug' => 'perchatki-kozhanye', 'name' => 'Перчатки кожаные', 'desc' => 'Натуральная кожа, для слесарных работ.', 'img' => '/template/demo/gloves/glove-08.svg', 'sort' => 8],
        ] as $item) {
            Product::query()->updateOrCreate(
                [
                    'category_id' => $gloves->id,
                    'slug' => $item['slug'],
                ],
                [
                    'name' => $item['name'],
                    'short_description' => $item['desc'],
                    'description' => $item['desc'],
                    'thumb_path' => $item['img'],
                    'sort' => $item['sort'],
                    'is_active' => true,
                ]
            );
        }
    }
}
