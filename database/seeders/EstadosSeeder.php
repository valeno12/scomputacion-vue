<?php

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = [
            'En revision',
            'Pendiente aprobación',
            'En proceso',
            'Finalizado',
            'Entregado'
        ];

        foreach($estados as $estado){
            Estado::create(['nombre' => $estado]);
        }
    }
}
