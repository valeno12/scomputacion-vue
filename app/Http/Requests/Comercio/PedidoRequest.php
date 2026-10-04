<?php

namespace App\Http\Requests\Comercio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            ...OperacionRequest::itemRules(),
            'productos' => 'prohibited',
            'cliente_id' => ['required', Rule::exists('cliente', 'id')->whereNull('deleted_at')],
            'equipo' => 'required|string|max:255',
            'estado_ingreso' => 'required|string|max:10000',
            'cargador' => 'required|boolean',
            'trabajo_realizar' => 'nullable|string|max:10000',
            'costo_mano_obra' => 'nullable|numeric|min:0|max:999999999|decimal:0,2',
            'reparto_mano_obra' => 'sometimes|array|max:100',
            'reparto_mano_obra.*.participante_id' => 'required|integer|exists:participantes,id',
            'reparto_mano_obra.*.porcentaje' => 'required|numeric|between:0,100|decimal:0,2',
            'cambiar_estado' => 'sometimes|boolean',
        ];
    }
}
