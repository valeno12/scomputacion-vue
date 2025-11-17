<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;
    protected $table = 'estado';

    public function pedidos()
    {
        return $this->belongsToMany(Pedido::class, 'pedido_estado')->withTimestamps();
    }
}
