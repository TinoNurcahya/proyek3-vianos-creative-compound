<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::job(new \App\Jobs\TrainAiModelsJob())->weekly()->mondays()->at('01:00');
Schedule::job(new \App\Jobs\TrainRecommenderJob())->weekly()->mondays()->at('02:00');
Schedule::job(new \App\Jobs\TrainCollaborativeFilteringJob())->weekly()->mondays()->at('03:00');
