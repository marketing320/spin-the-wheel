<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Return expired, unredeemed vouchers back onto the wheel automatically.
// Dormant until a cron entry runs `php artisan schedule:run` on the server;
// admins can also trigger it manually from the Vouchers page.
Schedule::command('vouchers:rotate')->hourly();
