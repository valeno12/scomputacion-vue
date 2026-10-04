<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido', function (Blueprint $table) {
            $table->unsignedSmallInteger('comercio_version')->default(1);
        });
        // Freeze the price used by the previous application, only for historical lines.
        Schema::table('productos_seleccionados', function (Blueprint $table) {
            $table->decimal('precio_venta', 14, 4)->nullable();
        });
        DB::table('productos_seleccionados')->update(['precio_venta' => DB::raw('precio * 1.30')]);

        Schema::create('participantes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('configuracion_comercial', function (Blueprint $table) {
            $table->id();
            $table->json('reparto_mercaderia');
            $table->json('reparto_mano_obra');
            $table->timestamps();
        });
        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('marca')->nullable();
            $table->unsignedBigInteger('precio_venta_centavos');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('lotes_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos');
            $table->foreignId('comprador_id')->constrained('participantes');
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->foreign('proveedor_id')->references('id')->on('proveedor');
            $table->unsignedInteger('cantidad_inicial');
            $table->unsignedInteger('cantidad_disponible');
            $table->unsignedBigInteger('costo_unitario_centavos');
            $table->string('tipo'); // compra | inicial
            $table->date('fecha');
            $table->timestamps();
        });
        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('clave')->unique();
            $table->string('tipo'); // venta | reparacion
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->foreign('pedido_id')->references('id')->on('pedido');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->foreign('cliente_id')->references('id')->on('cliente');
            $table->string('estado')->default('borrador');
            $table->date('fecha');
            $table->date('fecha_cobro')->nullable()->index();
            $table->string('medio_pago')->nullable();
            $table->bigInteger('costo_centavos')->default(0);
            $table->bigInteger('total_centavos')->default(0);
            $table->bigInteger('ganancia_centavos')->default(0);
            $table->timestamps();
        });
        Schema::create('compras_repuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones');
            $table->string('descripcion');
            $table->foreignId('comprador_id')->constrained('participantes');
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->foreign('proveedor_id')->references('id')->on('proveedor');
            $table->unsignedInteger('cantidad');
            $table->unsignedBigInteger('costo_unitario_centavos');
            $table->date('fecha');
            $table->timestamps();
        });
        Schema::create('operacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones');
            $table->foreignId('compra_id')->nullable()->constrained('compras_repuestos');
            $table->string('tipo'); // stock | repuesto | mano_obra
            $table->string('descripcion');
            $table->foreignId('lote_id')->nullable()->constrained('lotes_stock');
            $table->foreignId('comprador_id')->nullable()->constrained('participantes');
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->foreign('proveedor_id')->references('id')->on('proveedor');
            $table->unsignedInteger('cantidad');
            $table->unsignedBigInteger('costo_unitario_centavos');
            $table->unsignedBigInteger('precio_unitario_centavos');
            $table->date('fecha_compra')->nullable();
            $table->json('reparto');
            $table->json('distribucion');
            $table->timestamps();
        });
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes_stock');
            $table->foreignId('operacion_id')->nullable()->constrained('operaciones');
            $table->integer('cantidad'); // Positive for entries and returns.
            $table->string('motivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['inventario_movimientos', 'operacion_items', 'compras_repuestos', 'operaciones', 'lotes_stock', 'articulos', 'configuracion_comercial', 'participantes'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('productos_seleccionados', fn (Blueprint $table) => $table->dropColumn('precio_venta'));
        Schema::table('pedido', fn (Blueprint $table) => $table->dropColumn('comercio_version'));
    }
};
