<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only automatic, unpaid dates from the new order circuit. Paid snapshots and
        // manually dated sales/stock entries retain their original calendar dates.
        DB::table('operaciones')->whereNull('fecha_cobro')->whereNotNull('created_at')
            ->where('estado', '!=', 'anulada')
            ->whereIn('pedido_id', DB::table('pedido')->where('comercio_version', 2)->select('id'))
            ->where(fn ($query) => $query->where('tipo', 'reparacion')->orWhereNotNull('presupuesto_pedido_id'))
            ->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $created = CarbonImmutable::parse($row->created_at, 'UTC');
                    $localDate = $created->setTimezone(config('comercio.zona_horaria'))->toDateString();
                    if ($row->fecha !== $created->toDateString() || $row->fecha === $localDate) {
                        continue;
                    }
                    DB::table('operaciones')->where('id', $row->id)->whereNull('fecha_cobro')
                        ->where('fecha', $row->fecha)->update(['fecha' => $localDate]);
                }
            });
    }

    public function down(): void
    {
        // Do not restore incorrect dates or change a payment recorded after this correction.
    }
};
