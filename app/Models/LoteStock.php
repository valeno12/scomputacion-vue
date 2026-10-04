<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoteStock extends Model
{
    use \App\Models\Concerns\RegistraUsuario;

    protected $table = 'lotes_stock';

    protected $guarded = ['id'];

    protected $casts = ['cantidad_disponible' => 'integer', 'cantidad_inicial' => 'integer', 'costo_unitario_centavos' => 'integer'];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function comprador()
    {
        return $this->belongsTo(Participante::class, 'comprador_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
