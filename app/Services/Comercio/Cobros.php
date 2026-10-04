<?php

namespace App\Services\Comercio;

use App\Models\Operacion;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

final class Cobros
{
    public function __construct(private Operaciones $operaciones) {}

    public function venta(Operacion $venta, array $data): void
    {
        DB::transaction(function () use ($venta, $data) {
            if ($venta->pedido_id) {
                Pedido::withTrashed()->lockForUpdate()->findOrFail($venta->pedido_id);
            }
            $venta = Operacion::lockForUpdate()->findOrFail($venta->id);
            if ($venta->tipo !== 'venta' || $venta->estado === 'anulada') {
                $this->operaciones->error('Esta operación no admite un cobro.', 'cobro');
            }
            if ($venta->fecha_cobro) {
                return; // A double click never records or dates the same collection twice.
            }
            $this->registrar(collect([$venta]), $data);
        }, 3);
    }

    /** Called with the order locked, within its delivery transaction. */
    public function pedido(Pedido $pedido, array $data): void
    {
        $pendientes = $pedido->operaciones()->where('estado', '!=', 'anulada')
            ->whereNull('fecha_cobro')->orderBy('id')->lockForUpdate()->get();
        $this->registrar($pendientes, $data);
    }

    private function registrar($operaciones, array $data): void
    {
        if ($operaciones->sum('total_centavos') !== (int) $data['total_esperado']) {
            $this->operaciones->error('El saldo cambió. Cerrá esta ventana y actualizá la página para revisar el importe antes de cobrar.', 'total_esperado');
        }
        $cobradoEn = now();
        foreach ($operaciones as $operacion) {
            if ($data['fecha_cobro'] < $operacion->fecha) {
                $this->operaciones->error('El cobro no puede ser anterior a la operación.', 'fecha_cobro');
            }
            $this->operaciones->confirmar($operacion);
            $operacion->update(['fecha_cobro' => $data['fecha_cobro'], 'cobrado_en' => $cobradoEn, 'medio_pago' => $data['medio_pago']]);
        }
    }
}
