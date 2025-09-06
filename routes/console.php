<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule hourly due-date notifications
Schedule::command('taskit:send-due-notifications')->hourly();

// Schedule subscription changes processing every 5 minutes
Schedule::command('subscription:process-scheduled-changes')->everyFiveMinutes();

// Schedule completed tasks pruning daily at midnight
Schedule::command('tasks:prune-completed')->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
