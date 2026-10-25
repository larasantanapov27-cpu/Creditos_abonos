<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Aplicado', 'Cancelado'] as $estado) {
            DB::table('estado_abonos')->updateOrInsert(['estado_abono' => $estado], []);
        }
        DB::table('tasa_intereses')->updateOrInsert(['tasa' => '0.0000'], []);
        foreach (['Efectivo', 'Transferencia', 'Depósito'] as $tipo) {
            DB::table('tipo_pagos')->updateOrInsert(['tipo' => $tipo], []);
        }
    }
}
