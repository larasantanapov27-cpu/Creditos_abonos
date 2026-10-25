<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdaptarImportacion extends Command
{
    protected $signature = 'creditos:adaptar-importacion';

    protected $description = 'Preserva tablas del SQL original vacío y prepara una instalación limpia sin borrar tablas';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('Requiere MariaDB/mysql.');

            return 1;
        }
        $tables = [
            'estados',
            'estado_abonos',
            'municipios',
            'registro_abonos',
            'registro_clientes',
            'registro_creditos',
            'tasa_intereses',
            'tipo_pagos',
        ];
        foreach ($tables as $t) {
            if (! Schema::hasTable($t)) {
                $this->error('El esquema original no está completo. No se hicieron cambios.');

                return 1;
            }
            if (DB::table($t)->exists()) {
                $this->error('Hay datos en '.$t.'. Se requiere conversión supervisada sobre una copia, con mapeo de municipios y calendario. No se hicieron cambios. Consulta docs/importacion.md.');

                return 1;
            }
        }
        if (Schema::hasColumn('registro_clientes', 'activo')) {
            $this->error('El esquema ya está adaptado. Ejecuta migrate normalmente.');

            return 1;
        }
        if ($this->call('creditos:respaldar') !== 0) {
            return 1;
        }
        $suffix = now()->format('Ymd_His');
        $clauses = array_map(fn ($t) => "`$t` TO `legado_{$suffix}_{$t}`", $tables);
        DB::statement('RENAME TABLE '.implode(', ', $clauses));
        Log::info('Esquema original vacío preservado', ['prefijo' => 'legado_'.$suffix]);
        $this->info('Tablas originales conservadas como legado_'.$suffix.'_*. Ejecuta php artisan migrate --seed.');

        return 0;
    }
}
