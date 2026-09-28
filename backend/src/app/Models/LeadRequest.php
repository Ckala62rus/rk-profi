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
 * @property string $email_delivery_status Состояние доставки: queued, sending, sent, failed или dispatch_failed
 * @property string|null $email_recipient Адресат, выбранный при постановке письма в очередь
 * @property string|\Illuminate\Support\Carbon|null $email_queued_at Момент постановки письма в очередь
 * @property string|\Illuminate\Support\Carbon|null $email_started_at Момент последней попытки отправки
 * @property string|\Illuminate\Support\Carbon|null $email_failed_at Момент окончательной ошибки доставки
 * @property int $email_attempts Количество фактических попыток отправки
 * @property int $email_delivery_version Поколение уведомления для защиты от дублирования
 * @property string|null $email_error Последняя техническая ошибка доставки
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
        'email_delivery_status',
        'email_recipient',
        'email_queued_at',
        'email_started_at',
        'email_failed_at',
        'email_attempts',
        'email_delivery_version',
        'email_error',
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
            'email_queued_at' => 'datetime',
            'email_started_at' => 'datetime',
            'email_failed_at' => 'datetime',
            'email_attempts' => 'integer',
            'email_delivery_version' => 'integer',
        ];
    }
}
