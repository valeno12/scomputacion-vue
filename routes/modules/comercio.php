<?php

use App\Http\Controllers\ComercioController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('comercio')->name('comercio.')->controller(ComercioController::class)->group(function () {
    Route::redirect('/configuracion', '/settings/repartos')->name('configuracion');
    Route::put('/configuracion', 'guardarConfiguracion')->name('configuracion.guardar');
    Route::post('/participantes', 'participante')->name('participantes.crear');
    Route::put('/participantes/{participante}', 'participante')->name('participantes.actualizar');
    Route::get('/inventario', 'inventario')->name('inventario');
    Route::post('/articulos', 'articulo')->name('articulos.crear');
    Route::put('/articulos/{articulo}', 'articulo')->name('articulos.actualizar');
    Route::post('/articulos/{articulo}/ingresos', 'ingreso')->name('ingresos');
    Route::get('/ventas', 'ventas')->name('ventas');
    Route::get('/ventas/crear', 'crearVenta')->name('ventas.crear');
    Route::post('/ventas', 'vender')->name('ventas.guardar');
    Route::get('/operaciones/{operacion}', 'operacion')->name('operacion');
    Route::post('/operaciones/{operacion}/cobrar', 'cobrar')->name('cobrar');
    Route::post('/operaciones/{operacion}/anular', 'anular')->name('anular');
    Route::post('/repuestos/{item}/comprar', 'comprarRepuesto')->name('repuestos.comprar');
    Route::get('/movimientos', 'movimientos')->name('movimientos');
});
