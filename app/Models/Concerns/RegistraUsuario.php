<?php

namespace App\Models\Concerns;

use App\Models\User;

trait RegistraUsuario
{
    protected static function bootRegistraUsuario(): void
    {
        static::creating(function ($model): void {
            $model->registrado_por = auth()->id();
        });
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
