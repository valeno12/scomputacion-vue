<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoEstado extends Model
{
    use HasFactory;
    protected $table = 'pedido_estado';
    protected $fillable = ['pedido_id', 'estado_id'];
    public function Pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function Estado()
    {
        return $this->belongsTo(Estado::class);
    }
}
