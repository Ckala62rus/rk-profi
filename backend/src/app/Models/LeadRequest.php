<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Заявка с публичной формы «Отправить заявку».
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property string $message
 * @property array<int, mixed>|null $attachments Метаданные загруженных файлов
 * @property LeadStatus $status
 * @property string|\Illuminate\Support\Carbon|null $email_sent_at Момент успешной отправки письма менеджеру
 * @property string|null $ip
 * @property string|null $user_agent
 */
class LeadRequest extends Model
{
    /**
     * Массово заполняемые поля.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'message',
        'attachments',
        'status',
        'email_sent_at',
        'ip',
        'user_agent',
    ];

    /**
     * Приведения типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'status' => LeadStatus::class,
            'email_sent_at' => 'datetime',
        ];
    }
}
