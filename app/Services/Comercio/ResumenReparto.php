<?php

namespace App\Services\Comercio;

use App\Models\Operacion;

final class ResumenReparto
{
    public function operacion(Operacion $op): array
    {
        $totals = [];
        foreach ($op->items as $item) {
            foreach ($item->distribucion as $row) {
                $id = $row['participante_id'];
                $totals[$id] ??= ['participante_id' => $id, 'nombre' => $row['nombre'], 'rol' => $id == $op->hermana_id ? 'hermana' : ($id == $op->titular_id ? 'titular' : 'otro'), 'costo_centavos' => 0, 'ganancia_centavos' => 0, 'total_centavos' => 0];
                $totals[$id]['costo_centavos'] += $row['costo_centavos'];
                $totals[$id]['ganancia_centavos'] += $row['ganancia_centavos'];
                $totals[$id]['total_centavos'] += $row['costo_centavos'] + $row['ganancia_centavos'];
            }
        }
        // Show a zero share too, so the operator can distinguish it from missing configuration.
        foreach (['titular' => $op->titular_id, 'hermana' => $op->hermana_id] as $role => $id) {
            if ($id && ! isset($totals[$id])) {
                $totals[$id] = ['participante_id' => $id, 'nombre' => \App\Models\Participante::find($id)?->nombre ?? ucfirst($role), 'rol' => $role, 'costo_centavos' => 0, 'ganancia_centavos' => 0, 'total_centavos' => 0];
            }
        }

        return array_values($totals);
    }
}
