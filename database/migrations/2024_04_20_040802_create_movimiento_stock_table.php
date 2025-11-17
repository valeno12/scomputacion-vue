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
        Schema::create('movimiento_stock', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('producto_id');
            $table->unsignedInteger('pedido_id')->nullable();
            $table->unsignedInteger('proveedor_id')->nullable();
            $table->string('tipo_movimiento');
            $table->integer('cantidad');
            $table->dateTime('fecha');
            $table->decimal('precio',10,2)->nullable();
            $table->foreign('producto_id')->references('id')->on('producto');
            $table->foreign('pedido_id')->references('id')->on('pedido');
            $table->foreign('proveedor_id')->references('id')->on('proveedor');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_stock');
    }
};
