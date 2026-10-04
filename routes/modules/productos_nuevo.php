<?php

use App\Http\Controllers\ProductoNuevoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('productos')->name('productos_nuevo.')->controller(ProductoNuevoController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/buscar', 'buscar')->name('buscar');
    Route::get('/seleccion', 'seleccion')->name('seleccion');
    Route::post('/precios', 'aumentarPrecios')->name('precios.update');
    Route::get('/ingresos/create', 'nuevoIngreso')->name('ingresos.create');
    Route::post('/ingresos', 'guardarIngresoMultiple')->name('ingresos.multiple');
    Route::get('/{producto}/edit', 'edit')->name('edit');
    Route::get('/{producto}', 'show')->name('show');
    Route::put('/{producto}', 'update')->name('update');
    Route::delete('/{producto}', 'destroy')->name('destroy');
    Route::get('/{producto}/ingresos/create', 'ingreso')->name('ingreso');
    Route::post('/{producto}/ingresos', 'guardarIngreso')->name('ingresos.store');
});
