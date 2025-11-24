<?php

use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;


Route::prefix('Producto')->middleware(['auth', 'verified'])->group(function(){
    Route::get('/', [ProductoController::class, 'index'])->name('producto.index');
    Route::get('/create', [ProductoController::class, 'create'])->name('producto.create');
    Route::post('/', [ProductoController::class, 'store'])->name('producto.store');
    Route::get('/{id}/edit', [ProductoController::class, 'edit'])->name('producto.edit');
    Route::put('/{id}', [ProductoController::class, 'update'])->name('producto.update');
    Route::delete('/{id}', [ProductoController::class, 'destroy'])->name('producto.destroy');
    Route::get('/buscarNombre', [ProductoController::class, 'buscarNombre'])->name('producto.buscarNombre');
    Route::get('/buscarMarca', [ProductoController::class, 'buscarMarca'])->name('producto.buscarMarca');
    Route::get('/buscarProveedor', [ProductoController::class, 'buscarProovedor'])->name('producto.buscarProveedor');
    Route::get('/search', [ProductoController::class, 'search'])->name('producto.search');

});