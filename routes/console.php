<?php

use App\Models\VisitorRegistration;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => VisitorRegistration::query()
    ->where('status', 'pending')
    ->where('created_at', '<=', now()->subHours(2))
    ->update(['status' => 'expired', 'updated_at' => now()]))
    ->everyMinute()
    ->name('expire-pending-visitor-registrations')
    ->withoutOverlapping();

Schedule::call(fn (DatabaseBackupService $backups) => $backups->create())
    ->dailyAt('00:00')
    ->name('daily-gatepass-database-backup')
    ->withoutOverlapping();
