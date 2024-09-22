<?php

use App\Console\Commands\GenerateRoutingPlan;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('generate:routing-plan', function () {
    $this->call(GenerateRoutingPlan::class);
});
