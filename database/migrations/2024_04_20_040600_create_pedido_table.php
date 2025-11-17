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
        Schema::create('pedido', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('cliente_id');
            $table->unsignedInteger('estadoActual_id');
            $table->string('equipo');
            $table->string('codigo')->nullable();
            $table->boolean('cargador');
            $table->date('fecha_ingreso');
            $table->string('estado_ingreso');
            $table->string('trabajo_realizar')->nullable();
            $table->string('componente_problematico')->nullable();
            $table->decimal('presupuesto',10,2)->nullable();
            $table->decimal('ganancia', 10, 2)->nullable();
            $table->decimal('costo_mano_obra', 10, 2)->nullable();
            $table->decimal('costo',10,2)->nullable();
            $table->date('fecha_pago')->nullable();
            $table->foreign('cliente_id')->references('id')->on('cliente');
            $table->foreign('estadoActual_id')->references('id')->on('estado');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido');
        Schema::dropIfExists('equipo');
    }

};
