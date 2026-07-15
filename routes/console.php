<?php

use App\Models\Media;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Soft-deleted media keep their files on disk until they are force deleted, so trashed
 * rows past their retention window are pruned nightly to release the storage.
 */
Schedule::command('model:prune', ['--model' => [Media::class]])->daily();
