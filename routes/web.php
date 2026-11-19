<?php

use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoutineCheckController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\RoutineWeekController;
use Illuminate\Support\Facades\Route;

// Home → sign-up page. Logged-in users are sent on to the dashboard by the 'guest' middleware on /register.
Route::redirect('/', '/register');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::patch('/daily-tasks/{daily_task}/move', [DailyTaskController::class, 'move'])->name('daily-tasks.move');
    Route::patch('/daily-tasks/{daily_task}/complete', [DailyTaskController::class, 'complete'])->name('daily-tasks.complete');
    Route::patch('/daily-tasks/{daily_task}/reopen', [DailyTaskController::class, 'reopen'])->name('daily-tasks.reopen');
    Route::resource('daily-tasks', DailyTaskController::class)->except(['show']);

    // Daily Routine: menu link → this week's sheet; a sheet is addressed by its Monday's date.
    Route::get('/daily-routine', [RoutineWeekController::class, 'current'])->name('daily-routine');
    // Edit sheet: choose which routines are on this week's sheet (checkboxes → sync).
    Route::get('/routine-weeks/{date}/edit', [RoutineWeekController::class, 'edit'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('routine-weeks.edit');
    Route::patch('/routine-weeks/{date}', [RoutineWeekController::class, 'update'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('routine-weeks.update');

    Route::get('/routine-weeks/{date}', [RoutineWeekController::class, 'show'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('routine-weeks.show');
    // Create this week's sheet (copies last sheet's active routines). No date: the server uses this Monday.
    Route::post('/routine-weeks', [RoutineWeekController::class, 'store'])->name('routine-weeks.store');

    // Tick = POST (create a row), untick = DELETE (remove the row).
    Route::resource('routine-checks', RoutineCheckController::class)->only(['store', 'destroy']);

    // Routines: CRUD, except "show" (the weekly sheet shows all routines) and "create" (the form is on the index page).
    Route::patch('/routines/{routine}/deactivate', [RoutineController::class, 'deactivate'])->name('routines.deactivate');
    Route::patch('/routines/{routine}/activate', [RoutineController::class, 'activate'])->name('routines.activate');
    Route::resource('routines', RoutineController::class)->except(['show', 'create']);

});

require __DIR__.'/auth.php';
