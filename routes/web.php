<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManualReadingController;
use App\Http\Controllers\ActuatorController;
use App\Http\Controllers\ExportController;

Route::get('/', fn() => redirect()->route('login'));

require __DIR__.'/auth.php';

Route::middleware(['auth', 'check.status'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/site/{site}', [DashboardController::class, 'site'])->name('dashboard.site');
    Route::post('/dashboard/site/{site}/actuators/{parameter}/toggle', [ActuatorController::class, 'toggle'])->name('actuators.toggle');
    Route::get('/dashboard/{site}/{category}/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/sites/{site}/raw-data', [DashboardController::class, 'rawData'])->name('dashboard.raw-data');

    Route::get('/help', fn() => view('help'))->name('help');
    Route::middleware('admin')->get('/developer-docs', fn() => view('docs'))->name('docs');

    // Export
    Route::get('/export/{site}/{category}/group/{group}/csv',   [ExportController::class, 'groupCsv'])->name('export.group.csv');
    Route::get('/export/{site}/{category}/group/{group}/excel', [ExportController::class, 'groupExcel'])->name('export.group.excel');
    Route::get('/export/all-sites/excel', [ExportController::class, 'allSitesExcel'])->name('export.all-sites.excel');
    Route::get('/export/all-sites/csv',   [ExportController::class, 'allSitesCsv'])->name('export.all-sites.csv');
    Route::get('/export/{site}/all/excel',        [ExportController::class, 'allExcel'])->name('export.all.excel');
    Route::get('/export/{site}/all/csv',          [ExportController::class, 'allCsv'])->name('export.all.csv');
    Route::get('/export/{site}/{category}/excel', [ExportController::class, 'categoryExcel'])->name('export.category.excel');
    Route::get('/export/{site}/{category}/csv',   [ExportController::class, 'categoryCsv'])->name('export.category.csv');

    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Manual readings
    Route::post('/dashboard/site/{site}/manual-readings', [ManualReadingController::class, 'store'])
         ->name('manual-readings.store');
    Route::get('/dashboard/site/{site}/manual-readings/{parameter}/history', [ManualReadingController::class, 'history'])
         ->name('manual-readings.history');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        // Users
        Route::get('/users',                   [AdminController::class, 'users'])->name('users');
        Route::get('/users/create',            [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users',                  [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/{user}/edit',       [AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}',            [AdminController::class, 'updateUser'])->name('users.update');
        Route::post('/users/{user}/approve',   [AdminController::class, 'approveUser'])->name('users.approve');
        Route::post('/users/{user}/suspend',   [AdminController::class, 'suspendUser'])->name('users.suspend');
        Route::post('/users/{user}/activate',  [AdminController::class, 'activateUser'])->name('users.activate');
        Route::delete('/users/{user}',         [AdminController::class, 'destroyUser'])->name('users.destroy');

        // Sites
        Route::get('/sites',              [AdminController::class, 'sites'])->name('sites');
        Route::get('/sites/create',       [AdminController::class, 'createSite'])->name('sites.create');
        Route::post('/sites',             [AdminController::class, 'storeSite'])->name('sites.store');
        Route::get('/sites/{site}/edit',  [AdminController::class, 'editSite'])->name('sites.edit');
        Route::put('/sites/{site}',       [AdminController::class, 'updateSite'])->name('sites.update');
        Route::post('/sites/{site}/duplicate', [AdminController::class, 'duplicateSite'])->name('sites.duplicate');
        Route::delete('/sites/{site}',    [AdminController::class, 'destroySite'])->name('sites.destroy');

        // Site Users management
        Route::get('/sites/{site}/users',            [AdminController::class, 'siteUsers'])->name('sites.users');
        Route::post('/sites/{site}/users',           [AdminController::class, 'addUserToSite'])->name('sites.users.add');
        Route::delete('/sites/{site}/users/{user}',  [AdminController::class, 'removeUserFromSite'])->name('sites.users.remove');

        // Categories
        Route::get('/sites/{site}/categories',                    [AdminController::class, 'categories'])->name('categories');
        Route::post('/sites/{site}/categories',                   [AdminController::class, 'storeCategory'])->name('categories.store');
        Route::delete('/sites/{site}/categories/{category}',      [AdminController::class, 'destroyCategory'])->name('categories.destroy');
        Route::put('/sites/{site}/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
        Route::post('/sites/{site}/categories/{category}/toggle', [AdminController::class, 'toggleCategory'])->name('categories.toggle');

        // Parameters
        Route::get('/sites/{site}/categories/{category}/parameters',                     [AdminController::class, 'parameters'])->name('parameters');
        Route::post('/sites/{site}/categories/{category}/parameters',                    [AdminController::class, 'storeParameter'])->name('parameters.store');
        Route::delete('/sites/{site}/categories/{category}/parameters/{parameter}',      [AdminController::class, 'destroyParameter'])->name('parameters.destroy');
        Route::put('/sites/{site}/categories/{category}/parameters/{parameter}', [AdminController::class, 'updateParameter'])->name('parameters.update');
        Route::post('/sites/{site}/categories/{category}/parameters/{parameter}/toggle', [AdminController::class, 'toggleParameter'])->name('parameters.toggle');

        // Parameter Groups
        Route::post('/sites/{site}/categories/{category}/groups',                        [AdminController::class, 'storeParameterGroup'])->name('groups.store');
        Route::put('/sites/{site}/categories/{category}/groups/{group}',                  [AdminController::class, 'updateParameterGroup'])->name('groups.update');
        Route::delete('/sites/{site}/categories/{category}/groups/{group}',               [AdminController::class, 'destroyParameterGroup'])->name('groups.destroy');
        Route::post('/sites/{site}/categories/{category}/groups/reorder',                 [AdminController::class, 'reorderParameterGroups'])->name('groups.reorder');

        // Charts
        Route::get('/sites/{site}/categories/{category}/charts',                         [AdminController::class, 'charts'])->name('charts');
        Route::post('/sites/{site}/categories/{category}/charts',                        [AdminController::class, 'storeChart'])->name('charts.store');
        Route::put('/sites/{site}/categories/{category}/charts/{chart}',                 [AdminController::class, 'updateChart'])->name('charts.update');
        Route::post('/sites/{site}/categories/{category}/charts/{chart}/params',         [AdminController::class, 'updateChartParams'])->name('charts.updateParams');
        Route::delete('/sites/{site}/categories/{category}/charts/{chart}',              [AdminController::class, 'destroyChart'])->name('charts.destroy');
        Route::post('/sites/{site}/categories/{category}/charts/{chart}/toggle',         [AdminController::class, 'toggleChart'])->name('charts.toggle');
    });
});