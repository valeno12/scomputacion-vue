<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'producto';
    protected $fillable = [
        'nombre',
        'marca',
        'precio',
        'cantidad_disponible',
    ];

    public function detalles()
    {
        return $this->hasMany(PedidoProductos::class);
    }
    public function productosSeleccionados()
    {
        return $this->hasMany(ProductoSeleccionado::class, 'producto_id');
    }
}
