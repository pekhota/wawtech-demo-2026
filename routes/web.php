<?php

use App\Http\Controllers\Nova\ReorderController;
use App\Http\Requests\ReorderRequest;
use Illuminate\Support\Facades\Route;

// The site is only the admin: send visitors to Nova, which shows the login page to guests.
Route::redirect('/', config('nova.path'));

// Custom Nova endpoints sit under nova-vendor/ (Nova's convention) behind Nova's own auth and viewNova gate.
Route::middleware('nova:api')->prefix('nova-vendor')->group(function () {
    Route::post('{list}/reorder', ReorderController::class)
        ->whereIn('list', array_keys(ReorderRequest::LISTS))
        ->name('nova.reorder');
});
