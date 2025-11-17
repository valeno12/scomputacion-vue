<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoSeleccionado extends Model
{
    use HasFactory;
    protected $table='productos_seleccionados';
    protected $fillable = [
        'pedido_id',
        'producto_id',
        'cantidad',
        'precio'
    ];
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
