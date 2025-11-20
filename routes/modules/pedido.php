<?php

use App\Http\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

Route::prefix('Pedido')->group(function(){
 Route::get('/', [PedidoController::class, 'index'])->name('pedido.index');
 Route::get('/create', [PedidoController::class, 'create'])->name('pedido.create');
 Route::post('/', [PedidoController::class, 'store'])->name('pedido.store');
 Route::get('/{id}/showI', [PedidoController::class, 'showI'])->name('pedido.showI');
 Route::get('/{id}/editI', [PedidoController::class, 'editI'])->name('pedido.editI');
 Route::delete('/{id}', [PedidoController::class, 'destroy'])->name('pedido.destroy');
});