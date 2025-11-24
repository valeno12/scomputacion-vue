<?php

use App\Http\Controllers\RendimientosController;
use Illuminate\Support\Facades\Route;


Route::get('/rendimientos', [RendimientosController::class, 'index'])->middleware(['auth', 'verified'])->name('rendimientos.index');