<?php

use DerekBell\Games\Controllers\ApiController;

Route::prefix('api/derekbell/games')->middleware('throttle:60,1')->group(function() {

    // Games endpoints
    Route::get('/', [ApiController::class, 'games']);
    Route::get('/{gameSlug}', [ApiController::class, 'game']);

    // Episodes endpoints
    Route::get('/{gameSlug}/episodes', [ApiController::class, 'episodes']);
    Route::get('/{gameSlug}/episodes/{episodeSlug}', [ApiController::class, 'episode']);

    // Levels endpoints
    Route::get('/{gameSlug}/episodes/{episodeSlug}/levels', [ApiController::class, 'levels']);
    Route::get('/{gameSlug}/episodes/{episodeSlug}/levels/{levelSlug}', [ApiController::class, 'level']);

});
