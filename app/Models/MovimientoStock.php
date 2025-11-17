<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoStock extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'movimiento_stock';
    protected $fillable = [
        'producto_id',
        'pedido_id',
        'precio',
        'tipo_movimiento',
        'cantidad',
        'fecha',
        'proveedor_id',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
