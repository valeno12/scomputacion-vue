<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraRepuesto extends Model
{
    use \App\Models\Concerns\RegistraUsuario;

    protected $table = 'compras_repuestos';

    protected $guarded = ['id'];

    protected $casts = ['cantidad' => 'integer', 'costo_unitario_centavos' => 'integer', 'comprador_id' => 'integer'];

    public function comprador()
    {
        return $this->belongsTo(Participante::class, 'comprador_id');
    }

    public function operacion()
    {
        return $this->belongsTo(Operacion::class);
    }
}
