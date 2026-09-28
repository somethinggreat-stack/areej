<?php

use App\Models\Quote;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('quotes:expire', function () {
    $this->info(trans_choice('{0}No quotes expired.|{1}1 quote expired.|[2,*]:count quotes expired.', $count = Quote::expireOverdue(), ['count' => $count]));
})->purpose('Mark sent quotes past their valid-until date as expired');

// Also done whenever quotes are opened, so this only matters if cron is set up.
Schedule::command('quotes:expire')->dailyAt('00:10');
