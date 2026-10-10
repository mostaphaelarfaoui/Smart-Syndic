<?php

use Illuminate\Support\Facades\Schedule;

// Monthly dues reminder — runs on the 5th of every month
Schedule::command('smartsyndic:send-due-reminders')
    ->monthlyOn(5, '09:00')
    ->timezone('Africa/Casablanca');

// Expire stale trial tenants — daily at midnight
Schedule::command('smartsyndic:expire-trials')
    ->dailyAt('00:00')
    ->timezone('Africa/Casablanca');
