<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperacionItem extends Model
{
    protected $table = 'operacion_items';

    protected $guarded = ['id'];

    protected $casts = ['reparto' => 'array', 'distribucion' => 'array', 'cantidad' => 'integer', 'costo_unitario_centavos' => 'integer', 'precio_unitario_centavos' => 'integer', 'porcentaje_ganancia' => 'float'];

    public function lote()
    {
        return $this->belongsTo(LoteStock::class, 'lote_id');
    }

    public function operacion()
    {
        return $this->belongsTo(Operacion::class);
    }

    public function comprador()
    {
        return $this->belongsTo(Participante::class, 'comprador_id');
    }
}
