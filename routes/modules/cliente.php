<?php

use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

Route::prefix('Cliente')->group(function(){
    Route::get('/', [ClienteController::class, 'index'])->name('cliente.index');
    Route::get('/create', [ClienteController::class, 'create'])->name('cliente.create');
    Route::post('/', [ClienteController::class, 'store'])->name('cliente.store');
    Route::get('/{id}/edit', [ClienteController::class, 'edit'])->name('cliente.edit');
    Route::put('/{id}', [ClienteController::class, 'update'])->name('cliente.update');
    Route::delete('/{id}', [ClienteController::class, 'destroy'])->name('cliente.destroy');
    Route::get('/{id}/show', [ClienteController::class, 'show'])->name('cliente.show');
});
