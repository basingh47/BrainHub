<?php

use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\ProfileController;
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
});

require __DIR__.'/auth.php';
