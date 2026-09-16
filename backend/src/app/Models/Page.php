<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Контентная страница сайта (главная, о компании и т.д.).
 *
 * @property int $id
 * @property string $slug Уникальный слаг страницы
 * @property string $title Заголовок
 * @property array<string, mixed>|null $blocks Блоки контента (hero, about и др.)
 * @property bool $is_active Активна ли страница
 */
class Page extends Model
{
    /**
     * Массово заполняемые поля.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'blocks',
        'is_active',
    ];

    /**
     * Приведения типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
