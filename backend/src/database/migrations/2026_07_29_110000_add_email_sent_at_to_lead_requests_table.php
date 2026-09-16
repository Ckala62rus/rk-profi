<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Отметка времени успешной отправки письма менеджеру по заявке.
 *
 * В админке это используется для отображения статуса:
 * "Отправлено" / "Не отправлено".
 */
return new class extends Migration
{
    /**
     * Выполняет миграцию.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('lead_requests', function (Blueprint $table) {
            $table->timestamp('email_sent_at')->nullable()->after('status');
        });
    }

    /**
     * Откатывает миграцию.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('lead_requests', function (Blueprint $table) {
            $table->dropColumn('email_sent_at');
        });
    }
};

