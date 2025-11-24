<?php

use App\Http\Controllers\MovimientoStockController;
use Illuminate\Support\Facades\Route;


Route::prefix('movimientos-stock')->middleware(['auth', 'verified'])->name('movimientos-stock.')->group(function () {
    Route::get('/', [MovimientoStockController::class, 'index'])->name('index');
    Route::get('/{id}/edit', [MovimientoStockController::class, 'edit'])->name('edit');
    Route::put('/{id}', [MovimientoStockController::class, 'update'])->name('update');
});