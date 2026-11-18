<?php

use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalIdController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::get('/local-assets/app.css', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);

    return response()->file(resource_path('css/app.css'), ['Content-Type' => 'text/css; charset=UTF-8']);
})->name('local-assets.css');

Route::get('/local-assets/app.js', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);

    return response()->file(resource_path('js/app.js'), ['Content-Type' => 'text/javascript; charset=UTF-8']);
})->name('local-assets.js');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});

Route::get('/visit', [VisitorController::class, 'create'])->name('visitor.create');
Route::post('/visit', [VisitorController::class, 'store'])->middleware('throttle:10,1')->name('visitor.store');
Route::get('/visit/status', [VisitorController::class, 'status'])->name('visitor.status');
Route::get('/visit/pass-token', [VisitorController::class, 'passToken'])->middleware('throttle:60,1')->name('visitor.pass-token');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/password/change', [AuthController::class, 'editPassword'])->name('password.edit');
    Route::put('/password/change', [AuthController::class, 'updatePassword'])->name('password.update');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/my-id', [DigitalIdController::class, 'show'])->middleware('role:student,faculty,staff,administrator')->name('my-id');
    Route::get('/my-id/token', [DigitalIdController::class, 'token'])->middleware('role:student,faculty,staff,administrator')->name('my-id.token');

    Route::middleware('role:administrator,security')->group(function () {
        Route::get('/visitors', [VisitorController::class, 'index'])->name('visitors.index');
        Route::post('/visitors/{visitorRegistration}/decision', [VisitorController::class, 'decide'])->name('visitors.decision');
    });

    Route::middleware('role:administrator,security,laboratory,office')->group(function () {
        Route::get('/logs', [AccessLogController::class, 'index'])->name('logs.index');
        Route::get('/reports/access.csv', [ReportController::class, 'export'])->name('reports.access');
    });

    Route::middleware('role:administrator,security,laboratory,office')->group(function () {
        Route::get('/scanner', [ScanController::class, 'create'])->name('scanner');
        Route::post('/scanner/verify', [ScanController::class, 'store'])->middleware('throttle:60,1')->name('scanner.verify');
    });

    Route::middleware('role:administrator')->group(function () {
        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::put('/members/{user}', [MemberController::class, 'update'])->name('members.update');
        Route::post('/users/{user}/digital-id', [DigitalIdController::class, 'issue'])->name('users.digital-id.issue');
        Route::delete('/digital-ids/{digitalId}', [DigitalIdController::class, 'revoke'])->name('digital-ids.revoke');
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
        Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
    });
});
