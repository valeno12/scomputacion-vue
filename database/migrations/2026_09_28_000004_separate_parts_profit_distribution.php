<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_comercial', function (Blueprint $table) {
            $table->json('reparto_repuestos')->nullable();
        });
        // Se conserva el valor anterior como punto de partida; desde ahora son independientes.
        DB::table('configuracion_comercial')->update(['reparto_repuestos' => DB::raw('reparto_mercaderia')]);
    }

    public function down(): void
    {
        Schema::table('configuracion_comercial', fn (Blueprint $table) => $table->dropColumn('reparto_repuestos'));
    }
};
