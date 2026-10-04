<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => false,
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/modules/cliente.php';
require __DIR__.'/modules/movimientoStock.php';
require __DIR__.'/modules/rendimiento.php';
require __DIR__.'/modules/proveedor.php';
require __DIR__.'/modules/producto.php';
require __DIR__.'/modules/productos_legacy.php';
require __DIR__.'/modules/productos_nuevo.php';
require __DIR__.'/modules/pedido.php';
require __DIR__.'/settings.php';
require __DIR__.'/modules/comercio.php';
