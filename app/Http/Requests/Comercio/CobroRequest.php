<?php

namespace App\Http\Requests\Comercio;

use App\Services\Comercio\FechaComercial;
use Illuminate\Foundation\Http\FormRequest;

class CobroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'fecha_cobro' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.FechaComercial::hoy()],
            'medio_pago' => ['required', 'string', 'max:100'],
            'total_esperado' => ['required', 'integer', 'min:0', 'max:9007199254740991'],
        ];
    }
}
