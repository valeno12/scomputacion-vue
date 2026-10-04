<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solo cambia la tabla creada para el catálogo nuevo. `producto` sigue intacta.
        Schema::rename('articulos', 'productos_nuevo');
        Schema::table('lotes_stock', function (Blueprint $table) {
            $table->uuid('clave')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('lotes_stock', function (Blueprint $table) {
            $table->dropUnique(['clave']);
            $table->dropColumn('clave');
        });
        Schema::rename('productos_nuevo', 'articulos');
    }
};
