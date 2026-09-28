<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Аудит асинхронной доставки уведомлений о заявках.
 */
return new class extends Migration
{
    /**
     * Добавляет состояние, адресата, попытки и ошибку доставки.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('lead_requests', function (Blueprint $table) {
            // Старые неотправленные записи помечаем ошибочными, чтобы их можно
            // было явно повторить из админки, а не выдавать за активную очередь.
            $table->string('email_delivery_status')->default('failed')->after('email_sent_at');
            $table->string('email_recipient')->nullable()->after('email_delivery_status');
            $table->timestamp('email_queued_at')->nullable()->after('email_recipient');
            $table->timestamp('email_started_at')->nullable()->after('email_queued_at');
            $table->timestamp('email_failed_at')->nullable()->after('email_started_at');
            $table->unsignedSmallInteger('email_attempts')->default(0)->after('email_failed_at');
            $table->unsignedInteger('email_delivery_version')->default(0)->after('email_attempts');
            $table->text('email_error')->nullable()->after('email_delivery_version');
        });

        DB::table('lead_requests')
            ->whereNotNull('email_sent_at')
            ->update(['email_delivery_status' => 'sent']);
    }

    /**
     * Удаляет поля аудита доставки.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('lead_requests', function (Blueprint $table) {
            $table->dropColumn([
                'email_delivery_status',
                'email_recipient',
                'email_queued_at',
                'email_started_at',
                'email_failed_at',
                'email_attempts',
                'email_delivery_version',
                'email_error',
            ]);
        });
    }
};
