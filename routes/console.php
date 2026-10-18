<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Deletes visitors not seen for config('visitors.retention_days') days (App\Models\Visitor::prunable()).
Schedule::command('model:prune', ['--model' => [\App\Models\Visitor::class]])->daily();
