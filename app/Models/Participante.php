<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participante extends Model
{
    protected $table = 'participantes';

    protected $guarded = ['id'];

    protected $casts = ['activo' => 'boolean'];
}
