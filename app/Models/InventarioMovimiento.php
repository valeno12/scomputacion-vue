<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioMovimiento extends Model
{
    use \App\Models\Concerns\RegistraUsuario;

    protected $table = 'inventario_movimientos';

    protected $guarded = ['id'];

    protected $casts = ['cantidad' => 'integer'];

    public function lote()
    {
        return $this->belongsTo(LoteStock::class);
    }

    public function operacion()
    {
        return $this->belongsTo(Operacion::class);
    }
}
