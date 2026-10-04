<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operacion extends Model
{
    use \App\Models\Concerns\RegistraUsuario;

    protected static function booted(): void
    {
        static::creating(function ($op) {
            $config = ConfiguracionComercial::find(1);
            $op->titular_id = $config?->titular_id;
            $op->hermana_id = $config?->hermana_id;
        });
    }

    protected $appends = ['reparto_resumen', 'es_presupuesto'];

    public function getEsPresupuestoAttribute(): bool
    {
        return $this->presupuesto_pedido_id !== null;
    }

    public function getRepartoResumenAttribute(): array
    {
        return app(\App\Services\Comercio\ResumenReparto::class)->operacion($this);
    }

    protected $table = 'operaciones';

    protected $guarded = ['id'];

    protected $attributes = ['estado' => 'borrador'];

    protected $casts = ['total_centavos' => 'integer', 'costo_centavos' => 'integer', 'ganancia_centavos' => 'integer', 'cobrado_en' => 'datetime'];

    public function items()
    {
        return $this->hasMany(OperacionItem::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class)->withTrashed();
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
