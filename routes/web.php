<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperationsController;
use App\Livewire\DailyReports\DailyReportIndex;
use App\Livewire\DocumentRegulations\DocumentRegulationIndex;
use App\Livewire\DutySchedules\DutyScheduleIndex;
use App\Livewire\Mail\MailCenterIndex;
use App\Livewire\Operations\CrmIndex;
use App\Livewire\Operations\ProjectIndex;
use App\Livewire\Profile\ProfileEdit;
use App\Livewire\RolesPermissions\RolesPermissionsIndex;
use App\Livewire\Settings\SettingIndex;
use App\Livewire\Users\UserIndex;
use App\Livewire\WorkProgress\WorkProgressIndex;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'unlocked'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('can:dashboard.view')
        ->name('dashboard');

    Route::get('/users', UserIndex::class)
        ->middleware('can:user.view')
        ->name('users.index');

    Route::get('/projects', ProjectIndex::class)
        ->middleware('can:project.view')
        ->name('operations.projects');

    Route::get('/operation-reports', [OperationsController::class, 'daily'])
        ->middleware('can:report.view')
        ->name('operations.daily');

    Route::get('/staff-assignments', [OperationsController::class, 'staff'])
        ->middleware('can:staff.view')
        ->name('operations.staff');

    Route::get('/analytics', [OperationsController::class, 'analytics'])
        ->middleware('can:analytics.view')
        ->name('operations.analytics');

    Route::get('/crm', CrmIndex::class)
        ->middleware('can:crm.view')
        ->name('operations.crm');

    Route::get('/alerts', [OperationsController::class, 'alerts'])
        ->middleware('can:alert.view')
        ->name('operations.alerts');

    Route::get('/duty-schedules', DutyScheduleIndex::class)
        ->middleware('can:schedule.view')
        ->name('duty-schedules.index');

    Route::get('/settings', SettingIndex::class)
        ->middleware('can:setting.view')
        ->name('settings.index');

    Route::get('/roles-permissions', RolesPermissionsIndex::class)
        ->middleware('can:role.manage')
        ->name('roles-permissions.index');

    Route::get('/daily-reports', DailyReportIndex::class)
        ->middleware('can:report.view')
        ->name('daily-reports.index');

    Route::get('/work-progress', WorkProgressIndex::class)
        ->middleware('can:work_progress.view')
        ->name('work-progress.index');

    Route::get('/document-regulations', DocumentRegulationIndex::class)
        ->middleware('can:document.view')
        ->name('document-regulations.index');

    Route::get('/mail', MailCenterIndex::class)
        ->middleware('can:mail.view')
        ->name('mail.index');

    Route::get('/profile', ProfileEdit::class)
        ->name('profile.edit');
});

// Custom storage route fallback for hosting that blocks symlinks
Route::get('/storage/{path}', function (string $path) {
    $filePath = 'public/'.$path;

    if (! Storage::exists($filePath)) {
        abort(404);
    }

    $file = Storage::get($filePath);
    $type = Storage::mimeType($filePath);

    if (
        str_starts_with($type, 'text/') ||
        in_array($type, [
            'application/json',
            'application/javascript',
            'application/xml',
            'image/svg+xml',
        ])
    ) {
        $type .= '; charset=utf-8';
    }

    return Response::make($file, 200)->header('Content-Type', $type);
})->where('path', '.*');
