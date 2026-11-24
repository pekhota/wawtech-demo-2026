<?php

use App\Http\Controllers\Nova\ReorderController;
use App\Http\Requests\ReorderRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Custom Nova endpoints sit under nova-vendor/ (Nova's convention) behind Nova's own auth and viewNova gate.
Route::middleware('nova:api')->prefix('nova-vendor')->group(function () {
    Route::post('{list}/reorder', ReorderController::class)
        ->whereIn('list', array_keys(ReorderRequest::LISTS))
        ->name('nova.reorder');
});
