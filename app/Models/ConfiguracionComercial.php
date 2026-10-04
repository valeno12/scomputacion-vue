<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionComercial extends Model
{
    protected $table = 'configuracion_comercial';

    protected $guarded = [];

    protected $casts = ['reparto_mercaderia' => 'array', 'reparto_repuestos' => 'array', 'reparto_mano_obra' => 'array'];
}
