<?php

namespace App\Enums;

/**
 * Статусы заявки с публичной формы.
 */
enum LeadStatus: string
{
    /** Новая заявка, ещё не обработана */
    case New = 'new';

    /** Заявка в работе у менеджера */
    case InProgress = 'in_progress';

    /** Заявка закрыта / обработана */
    case Done = 'done';

    /** Заявка отклонена / спам */
    case Rejected = 'rejected';
}
