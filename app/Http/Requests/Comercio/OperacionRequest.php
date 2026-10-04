<?php

namespace App\Http\Requests\Comercio;

use App\Services\Comercio\FechaComercial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public static function itemRules(): array
    {
        return [
            'items' => ['present', 'array', 'max:100'],
            'items.*.tipo' => ['required', Rule::in(['stock', 'repuesto'])],
            'items.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'items.*.grupo' => ['nullable', 'uuid', 'distinct'],
            'items.*.producto_id' => ['nullable', 'integer', 'exists:productos_nuevo,id'],
            'items.*.compra_id' => ['nullable', 'integer'],
            'items.*.descripcion' => ['nullable', 'string', 'max:255'],
            'items.*.lote_id' => ['nullable', 'integer', 'exists:lotes_stock,id'],
            'items.*.comprador_id' => ['nullable', 'integer', 'exists:participantes,id'],
            'items.*.proveedor_id' => ['nullable', 'integer', 'exists:proveedor,id'],
            'items.*.proveedor' => ['nullable', 'string', 'max:255'],
            'items.*.porcentaje_ganancia' => ['nullable', 'numeric', 'between:0,10000', 'decimal:0,2'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.costo' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],
            'items.*.precio' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],
            'items.*.fecha_compra' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.FechaComercial::hoy()],
            'items.*.reparto' => ['sometimes', 'array', 'max:100'],
            'items.*.reparto.*.participante_id' => ['required', 'integer', 'exists:participantes,id'],
            'items.*.reparto.*.porcentaje' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
        ];
    }

    public function rules(): array
    {
        return [
            ...self::itemRules(),
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'clave' => ['required', 'uuid'],
            'pedido_id' => ['nullable', 'integer', 'exists:pedido,id'],
            'cliente_id' => ['nullable', 'integer', 'exists:cliente,id'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.FechaComercial::hoy()],
            'cobrar' => ['sometimes', 'boolean'],
            'fecha_cobro' => ['required_if:cobrar,true', 'nullable', 'date_format:Y-m-d', 'after_or_equal:fecha', 'before_or_equal:'.FechaComercial::hoy()],
            'medio_pago' => ['required_if:cobrar,true', 'nullable', 'string', 'max:100'],
        ];
    }
}
