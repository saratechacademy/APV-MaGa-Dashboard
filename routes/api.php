<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes — APV-MaGa (Dynamic)
|--------------------------------------------------------------------------
|
| POST /api/sensors/{site}/{category}         → Enregistrer des lectures
| GET  /api/sensors/{site}/{category}/latest  → Dernière lecture
| GET  /api/sensors/{site}/status             → Statut de toutes les catégories
|
*/

Route::prefix('sensors')->group(function () {

    // Statut global du site
    Route::get('{site}/status', [ApiController::class, 'status']);

    // Dernière lecture par catégorie
    Route::get('{site}/{category}/latest', [ApiController::class, 'latest']);

    // Enregistrer des lectures (ESP32 → API)
    Route::post('{site}/{category}', [ApiController::class, 'store']);

});