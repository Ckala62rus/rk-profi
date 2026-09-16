<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Настройка сайта: одна запись key → JSON value.
 *
 * @property int $id
 * @property string $key Ключ настройки (например contacts)
 * @property array<string, mixed>|null $value Значение в JSON
 */
class SiteSetting extends Model
{
    /**
     * Массово заполняемые поля.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Приведения типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
