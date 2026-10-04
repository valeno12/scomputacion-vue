<?php

use App\Http\Controllers\MovimientoStockController;
use Illuminate\Support\Facades\Route;

Route::prefix('movimientos-stock')->middleware(['auth', 'verified'])->name('movimientos-stock.')->group(function () {
    Route::get('/', [MovimientoStockController::class, 'index'])->name('index');
    Route::get('/ingresos/{lote}/edit', [MovimientoStockController::class, 'editIngreso'])->name('ingresos.edit');
    Route::put('/ingresos/{lote}', [MovimientoStockController::class, 'updateIngreso'])->name('ingresos.update');
    Route::get('/{id}/edit', [MovimientoStockController::class, 'edit'])->name('edit');
    Route::put('/{id}', [MovimientoStockController::class, 'update'])->name('update');
});
