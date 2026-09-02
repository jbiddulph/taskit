<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule hourly due-date notifications
Schedule::command('taskit:send-due-notifications')->hourly();

// Create compliance reminder tasks daily
Schedule::command('compliance:generate-upcoming-tasks')->daily();

// Email and in-app expiry reminders for company users
Schedule::command('compliance:send-expiry-reminders')->dailyAt('08:00');

// Schedule subscription changes processing every 5 minutes
Schedule::command('subscription:process-scheduled-changes')->everyFiveMinutes();

// Schedule completed tasks pruning daily at midnight
Schedule::command('tasks:prune-completed')->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
