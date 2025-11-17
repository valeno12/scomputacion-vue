<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $table = 'cliente';
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'mail',
        'telefono',
        'direccion',
    ];

    // Relación con los pedidos
    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}
