<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\RunSecurityTests;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register security testing command
Artisan::add(new RunSecurityTests(app(\App\Services\SecurityTestingService::class)));
