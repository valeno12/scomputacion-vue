<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['configuracion_comercial', 'operaciones'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('titular_id')->nullable()->constrained('participantes');
                $table->foreignId('hermana_id')->nullable()->constrained('participantes');
            });
        }
        foreach (['productos_nuevo', 'lotes_stock', 'operaciones', 'compras_repuestos', 'inventario_movimientos'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete());
        }
        Schema::table('operacion_items', function (Blueprint $table) {
            $table->uuid('grupo')->nullable()->index();
            $table->foreignId('producto_id')->nullable()->constrained('productos_nuevo');
            $table->decimal('porcentaje_ganancia', 7, 2)->nullable();
            $table->string('proveedor_nombre')->nullable();
        });
        // Reuse a clearly identified owner; otherwise create the fixed business participant.
        $config = DB::table('configuracion_comercial')->where('id', 1)->first();
        $parts = json_decode($config?->reparto_repuestos ?? '[]', true);
        $products = json_decode($config?->reparto_mercaderia ?? '[]', true);
        $owner = count($parts) === 1 && (float) $parts[0]['porcentaje'] === 100.0 ? $parts[0]['participante_id'] : null;
        if (! $owner) {
            $owner = DB::table('participantes')->insertGetId(['nombre' => 'Titular', 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('participantes')->where('id', $owner)->update(['activo' => true]);
        $others = array_values(array_unique(array_filter(array_column($products, 'participante_id'), fn ($id) => $id != $owner)));
        $ownerSplit = [['participante_id' => $owner, 'porcentaje' => 100]];
        DB::table('configuracion_comercial')->updateOrInsert(['id' => 1], [
            'titular_id' => $owner, 'hermana_id' => count($others) === 1 ? $others[0] : null,
            'reparto_mercaderia' => json_encode($products ?: $ownerSplit),
            'reparto_repuestos' => json_encode($ownerSplit), 'reparto_mano_obra' => json_encode($ownerSplit),
            'created_at' => $config?->created_at ?? now(), 'updated_at' => now(),
        ]);
        DB::table('operaciones')->update(['titular_id' => $owner, 'hermana_id' => count($others) === 1 ? $others[0] : null]);
    }

    public function down(): void
    {
        Schema::table('operacion_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_id');
            $table->dropIndex(['grupo']);
            $table->dropColumn(['grupo', 'porcentaje_ganancia', 'proveedor_nombre']);
        });
        foreach (['productos_nuevo', 'lotes_stock', 'operaciones', 'compras_repuestos', 'inventario_movimientos'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('registrado_por'));
        }
        foreach (['configuracion_comercial', 'operaciones'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('titular_id');
                $table->dropConstrainedForeignId('hermana_id');
            });
        }
    }
};
