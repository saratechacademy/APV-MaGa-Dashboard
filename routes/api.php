<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

Route::prefix('sensors')->group(function () {
    Route::get('{site}/status',              [ApiController::class, 'status']);
    Route::get('{site}/{category}/latest',   [ApiController::class, 'latest']);
    Route::get('{site}/{category}/history',  [ApiController::class, 'history']);
    Route::get('{site}/{category}/stats',    [ApiController::class, 'stats']);
    Route::post('{site}/{category}',         [ApiController::class, 'store']);
});

// Commandes pour les actionneurs (vannes, ventilateurs, pompes...)
// L'ESP32 interroge cet endpoint pour savoir quel état appliquer.
Route::prefix('commands')->group(function () {
    Route::get('{site}/{category}',          [ApiController::class, 'commands']);
});