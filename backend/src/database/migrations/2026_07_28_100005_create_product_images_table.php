<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Галерея изображений товара.
 */
return new class extends Migration
{
    /**
     * Создаёт таблицу product_images.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Откатывает миграцию.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
