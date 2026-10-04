<?php

use App\Http\Controllers\ProductoLegacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('productos_legacy')->name('productos_legacy.')->controller(ProductoLegacyController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{id}/edit', 'edit')->name('edit');
    Route::put('/{id}', 'update')->name('update');
    Route::delete('/{id}', 'destroy')->name('destroy');
});
