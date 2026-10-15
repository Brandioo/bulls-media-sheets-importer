<?php

use App\Http\Controllers\ConfigurationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app');

Route::prefix('api')->group(function () {
    Route::get(
        '/configuration',
        [ConfigurationController::class, 'index']
    );

    Route::post(
        '/sources',
        [ConfigurationController::class, 'storeSource']
    );

    Route::put(
        '/sources/{source}',
        [ConfigurationController::class, 'updateSource']
    );

    Route::delete(
        '/sources/{source}',
        [ConfigurationController::class, 'destroySource']
    );

    Route::post(
        '/sources/{source}/import',
        [ConfigurationController::class, 'importSource']
    );

    Route::post(
        '/tabs',
        [ConfigurationController::class, 'storeTab']
    );

    Route::put(
        '/tabs/{tab}',
        [ConfigurationController::class, 'updateTab']
    );

    Route::delete(
        '/tabs/{tab}',
        [ConfigurationController::class, 'destroyTab']
    );

    Route::post(
        '/targets',
        [ConfigurationController::class, 'storeTarget']
    );

    Route::put(
        '/targets/{target}',
        [ConfigurationController::class, 'updateTarget']
    );

    Route::delete(
        '/targets/{target}',
        [ConfigurationController::class, 'destroyTarget']
    );

    Route::post(
        '/mappings',
        [ConfigurationController::class, 'storeMapping']
    );

    Route::put(
        '/mappings/{mapping}',
        [ConfigurationController::class, 'updateMapping']
    );

    Route::delete(
        '/mappings/{mapping}',
        [ConfigurationController::class, 'destroyMapping']
    );
});
