<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\User;
use App\Services\CreditoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        $u = User::firstOrCreate(['username' => 'demo'], [
            'name' => 'Encargado de demostración',
            'email' => 'demo@example.test',
            'password' => 'DemoLocal2026!',
        ]);
        DB::table('estados')->updateOrInsert(['nombre_estado' => 'Estado de México'], []);
        $e = DB::table('estados')->where('nombre_estado', 'Estado de México')->value('id_estado');
        DB::table('municipios')->updateOrInsert(['nombre_municipio' => 'Valle de Bravo', 'id_estado' => $e], []);
        $m = DB::table('municipios')->where('nombre_municipio', 'Valle de Bravo')->where('id_estado', $e)->value('id_municipio');
        $s = app(CreditoService::class);
        foreach ([
            ['Ana', 'López', 'García', '5510000001', 'LOGA900101MMCPRN01'],
            ['Luis', 'Pérez', 'Soto', '5510000002', 'PESL920202HMCRTS02'],
            ['María', 'Torres', 'Díaz', '5510000003', 'TODM950303MMCRZR03'],
        ] as $i => $p) {
            $c = Cliente::firstOrCreate(['curp' => $p[4]], [
                'nombre' => $p[0],
                'apellido_paterno' => $p[1],
                'apellido_materno' => $p[2],
                'telefono' => $p[3],
                'correo_electronico' => 'cliente'.($i + 1).'@example.test',
                'id_estado' => $e,
                'id_municipio' => $m,
                'codigo_postal' => '51200',
                'direccion' => 'Domicilio de demostración',
                'fecha_registro_cliente' => today(),
                'activo' => true,
            ]);
            if ($c->creditos()->exists()) {
                continue;
            }
            $first = today()->addDays(($i === 2 ? -42 : 7));
            $end = $first->copy()->addDays(21);
            $cr = $s->guardar([
                'id_cliente' => $c->getKey(),
                'motivo_credito' => 'Crédito de demostración',
                'monto_credito' => (string) (4000 + $i * 2000),
                'fecha_aprobacion' => $first->copy()->subDays(7)->toDateString(),
                'fecha_pago' => $first->toDateString(),
                'fecha_vencimiento' => $end->toDateString(),
                'cantidad_pagos' => 4,
                'periodicidad' => 'semanal',
                'id_tasa_interes' => DB::table('tasa_intereses')->value('id_tasa_interes'),
                'id_tipo_pago' => DB::table('tipo_pagos')->value('id_tipo_pago'),
            ], $u->id);
            $s->abonar($cr, $i === 1 ? '6000' : '1000', (string) Str::uuid(), $u->id);
        }
    }
}
