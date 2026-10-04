<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos_nuevo', function (Blueprint $table) {
            $table->unsignedBigInteger('costo_referencia_centavos')->nullable();
            $table->decimal('porcentaje_ganancia', 7, 2)->nullable();
        });
        Schema::create('ingresos_stock', function (Blueprint $table) {
            $table->id();
            $table->uuid('clave')->unique();
            $table->timestamps();
        });
        Schema::table('lotes_stock', function (Blueprint $table) {
            $table->foreignId('ingreso_stock_id')->nullable()->constrained('ingresos_stock');
            $table->decimal('porcentaje_ganancia', 7, 2)->nullable();
            $table->unsignedBigInteger('precio_venta_centavos')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lotes_stock', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ingreso_stock_id');
            $table->dropColumn(['porcentaje_ganancia', 'precio_venta_centavos']);
        });
        Schema::dropIfExists('ingresos_stock');
        Schema::table('productos_nuevo', function (Blueprint $table) {
            $table->dropColumn(['costo_referencia_centavos', 'porcentaje_ganancia']);
        });
    }
};
