<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;

// Root → login
Route::get('/', fn() => redirect()->route('login'));

// Auth routes (Breeze)
require __DIR__.'/auth.php';

// Protected routes
Route::middleware(['auth', 'check.status'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/site/{site}', [DashboardController::class, 'site'])->name('dashboard.site');

    // Profile
    Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');

    // Sites (CRUD)
    Route::resource('sites', SiteController::class);

    // Agriculture records
    Route::post('/dashboard/site/{site}/agriculture', [DashboardController::class, 'storeAgriculture'])
         ->name('agriculture.store');

    // Admin routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard',          [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users',              [AdminController::class, 'users'])->name('users');
        Route::post('/users/{user}/approve',  [AdminController::class, 'approveUser'])->name('users.approve');
        Route::post('/users/{user}/suspend',  [AdminController::class, 'suspendUser'])->name('users.suspend');
        Route::post('/users/{user}/activate', [AdminController::class, 'activateUser'])->name('users.activate');
        Route::delete('/users/{user}',        [AdminController::class, 'destroyUser'])->name('users.destroy');
    });
});
