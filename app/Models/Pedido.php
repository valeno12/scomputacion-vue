<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $table = 'pedido';

    protected $fillable = [
        'cliente_id',
        'cargador',
        'equipo',
        'id_movimiento_stock',
        'fecha_ingreso',
        'estado_ingreso',
        'trabajo_realizar',
        'repuesto',
        'presupuesto',
        'precio_total',
        'costo',
        'fecha_pago',
        'costo_mano_obra',
        'estadoActual_id',
    ];

    // Relación con el cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function productosSeleccionados()
    {
        return $this->hasMany(ProductoSeleccionado::class, 'pedido_id');
    }


    public function estados()
    {
        return $this->belongsToMany(Estado::class, 'pedido_estado', 'pedido_id', 'estado_id')
                    ->withTimestamps(); // Si la tabla pivote tiene columnas "created_at" y "updated_at"
    }

    public function estadoActual()
    {
        return $this->belongsTo(Estado::class, 'estadoActual_id');
    }

    public function estadoFinalizado()
    {
        return $this->hasOne(PedidoEstado::class, 'pedido_id')->where('estado_id', 4);
    }

    public function estadoEntregado()
    {
        return $this->hasOne(PedidoEstado::class, 'pedido_id')->where('estado_id', 5);
    }

}
