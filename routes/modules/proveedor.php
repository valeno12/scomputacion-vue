<?php

use App\Http\Controllers\ProveedorController;
use Illuminate\Support\Facades\Route;

Route::prefix('Proveedor')->middleware(['auth', 'verified'])->group(function(){
        Route::get('/', [ProveedorController::class, 'index'])->name('proveedor.index');
        Route::get('/create', [ProveedorController::class, 'create'])->name('proveedor.create');
        Route::post('/', [ProveedorController::class, 'store'])->name('proveedor.store');
        Route::get('/{id}/edit', [ProveedorController::class, 'edit'])->name('proveedor.edit');
        Route::put('/{id}', [ProveedorController::class, 'update'])->name('proveedor.update');
        Route::delete('/{id}', [ProveedorController::class, 'destroy'])->name('proveedor.destroy');
        Route::get('/search', [ProveedorController::class, 'search'])->name('proveedor.search');
});
