<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedido_estado', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('estado_id');
            $table->unsignedInteger('pedido_id');
            $table->foreign('estado_id')->references('id')->on('estado');
            $table->foreign('pedido_id')->references('id')->on('pedido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_estado');
    }
};
