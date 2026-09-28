<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// После аварийного завершения worker делает заявку доступной для retry в админке.
Schedule::command('leads:recover-delivery --minutes=30')->everyFiveMinutes()->withoutOverlapping();
