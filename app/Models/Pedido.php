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
        'ganancia',
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

        public function generarCodigo()
    {
        $cliente = $this->cliente;
        $prefijo = 'PE';
        $iniciales = '';
        
        $nombres_array = explode(' ', $cliente->nombre);
        foreach ($nombres_array as $nombre) {
            $iniciales .= strtoupper(substr($nombre, 0, 1));
        }
        
        $apellidos_array = explode(' ', $cliente->apellido);
        foreach ($apellidos_array as $apellido) {
            $iniciales .= strtoupper(substr($apellido, 0, 1));
        }
        
        $numero_id_formateado = str_pad($this->id, 3, '0', STR_PAD_LEFT);
        $codigo = $prefijo . $numero_id_formateado . $iniciales;
        
        $this->codigo = $codigo;
        $this->save();
    }
}
