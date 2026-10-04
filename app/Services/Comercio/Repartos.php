<?php

namespace App\Services\Comercio;

use App\Models\ConfiguracionComercial;
use App\Models\Participante;
use Illuminate\Validation\ValidationException;

final class Repartos
{
    public function titular(): Participante
    {
        $id = ConfiguracionComercial::find(1)?->titular_id;
        $person = $id ? Participante::find($id) : null;
        if (! $person) {
            throw ValidationException::withMessages(['items' => 'Configurá el participante principal en Repartos antes de operar.']);
        }

        return $person;
    }

    public function predeterminado(string $tipo): array
    {
        if ($tipo !== 'stock') {
            return $this->snapshot([['participante_id' => $this->titular()->id, 'porcentaje' => 100]]);
        }

        return $this->snapshot(ConfiguracionComercial::find(1)?->reparto_mercaderia ?? [], 'items');
    }

    public function snapshot(array $rows, string $field = 'reparto'): array
    {
        $ids = array_column($rows, 'participante_id');
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([$field => 'Cada participante puede aparecer una sola vez.']);
        }
        $people = Participante::where('activo', true)->whereIn('id', $ids)->get()->keyBy('id');
        $snapshot = [];
        foreach ($rows as $row) {
            $person = $people->get($row['participante_id']);
            if (! $person) {
                throw ValidationException::withMessages([$field => 'Seleccioná participantes activos para el reparto.']);
            }
            $snapshot[] = ['participante_id' => $person->id, 'nombre' => $person->nombre, 'porcentaje' => $row['porcentaje']];
        }
        try {
            Dinero::repartir(0, $snapshot);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([$field => 'Los porcentajes deben sumar exactamente 100 %.']);
        }

        return $snapshot;
    }

    public function opciones(): array
    {
        $config = ConfiguracionComercial::find(1);

        return [
            'titular' => $config?->titular_id ? Participante::find($config->titular_id) : null,
            'hermanaId' => $config?->hermana_id,
            'participantes' => Participante::where('activo', true)->orderBy('nombre')->get(),
            'repartoMercaderia' => $config?->reparto_mercaderia ?? [],
            'repartoRepuestos' => $config?->reparto_repuestos ?? $config?->reparto_mercaderia ?? [],
            'repartoManoObra' => $config?->reparto_mano_obra ?? [],
        ];
    }
}
