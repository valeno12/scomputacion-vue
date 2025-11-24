<?php

use App\Http\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

Route::prefix('Pedido')->middleware(['auth', 'verified'])->group(function(){
 Route::get('/', [PedidoController::class, 'index'])->name('pedido.index');
 Route::get('/create', [PedidoController::class, 'create'])->name('pedido.create');
 Route::post('/', [PedidoController::class, 'store'])->name('pedido.store');
 Route::get('/{id}/showI', [PedidoController::class, 'showI'])->name('pedido.showI');
 Route::get('/{id}/editI', [PedidoController::class, 'editI'])->name('pedido.editI');
 Route::get('/{id}/editF', [PedidoController::class, 'editF'])->name('pedido.editF');
 Route::get('/{id}/edit', [PedidoController::class, 'edit'])->name('pedido.edit');
 Route::put('/{id}', [PedidoController::class, 'update'])->name('pedido.update');
 Route::get('/{id}', [PedidoController::class, 'show'])->name('pedido.show');
 Route::delete('/{id}', [PedidoController::class, 'destroy'])->name('pedido.destroy');
 Route::delete('{pedido_id}/estados/{estado_id}', [PedidoController::class, 'eliminarEstado'])->name('pedido.eliminarestado');
 Route::get('/{pedido_id}/actualizarEstado/{estado_id}', [PedidoController::class, 'actualizarEstado'])->name('pedido.actualizarestado');
 Route::put('/{id}/genera_presupuesto',[PedidoController::class, 'storeAprobacion'])->name('pedido.storeAprobacion');
});