<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('presupuesto_pedido_id')->nullable()->unique();
            $table->foreign('presupuesto_pedido_id')->references('id')->on('pedido');
            $table->timestamp('anulada_el')->nullable();
        });

        $this->separateOrderSales();
    }

    public function separateOrderSales(): void
    {
        // Move existing snapshots intact. This neither allocates inventory nor records a payment.
        DB::transaction(function () {
            $operations = DB::table('operaciones')->where('tipo', 'reparacion')
                ->whereIn('pedido_id', DB::table('pedido')->where('comercio_version', 2)->select('id'))->get();
            foreach ($operations as $operation) {
                $items = DB::table('operacion_items')->where('operacion_id', $operation->id)->where('tipo', 'stock')->get();
                if ($items->isEmpty()) {
                    continue;
                }
                $cost = $items->sum(fn ($item) => $item->cantidad * $item->costo_unitario_centavos);
                $total = $items->sum(fn ($item) => $item->cantidad * $item->precio_unitario_centavos);
                $sale = (array) $operation;
                unset($sale['id']);
                $sale = array_replace($sale, ['clave' => (string) Str::uuid(), 'tipo' => 'venta',
                    'presupuesto_pedido_id' => $operation->pedido_id, 'costo_centavos' => $cost,
                    'total_centavos' => $total, 'ganancia_centavos' => $total - $cost]);
                $id = DB::table('operaciones')->insertGetId($sale);
                DB::table('operacion_items')->whereIn('id', $items->pluck('id'))->update(['operacion_id' => $id]);
                DB::table('inventario_movimientos')->where('operacion_id', $operation->id)->update(['operacion_id' => $id]);
                DB::table('operaciones')->where('id', $operation->id)->update([
                    'costo_centavos' => $operation->costo_centavos - $cost,
                    'total_centavos' => $operation->total_centavos - $total,
                    'ganancia_centavos' => $operation->ganancia_centavos - ($total - $cost),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Sales remain valid associated sales in the previous schema; never discard their records.
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropForeign(['presupuesto_pedido_id']);
            $table->dropUnique(['presupuesto_pedido_id']);
            $table->dropColumn(['presupuesto_pedido_id', 'anulada_el']);
        });
    }
};
