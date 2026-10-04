<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoNuevo extends Model
{
    use \App\Models\Concerns\RegistraUsuario;

    protected $table = 'productos_nuevo';

    protected $guarded = ['id'];

    protected $casts = [
        'activo' => 'boolean', 'precio_venta_centavos' => 'integer',
        'cantidad_disponible' => 'integer', 'costo_unitario_centavos' => 'integer',
        'costo_referencia_centavos' => 'integer', 'porcentaje_ganancia' => 'float',
    ];

    public function lotes()
    {
        return $this->hasMany(LoteStock::class, 'articulo_id');
    }
}
