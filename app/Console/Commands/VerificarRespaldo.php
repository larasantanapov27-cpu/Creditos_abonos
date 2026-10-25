<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class VerificarRespaldo extends Command
{
    protected $signature = 'creditos:verificar-respaldo {archivo} {--database= : Base nueva con prefijo creditos_restauracion_}';

    protected $description = 'Restaura en una base NUEVA separada y compara conteos y huellas de todos los registros';

    public function handle(): int
    {
        $target = (string) $this->option('database');
        $file = realpath($this->argument('archivo'));
        if (config('database.default') !== 'mysql' || ! preg_match('/^creditos_restauracion_[a-zA-Z0-9_]{1,40}$/', $target) || ! $file || ! is_file($file)) {
            $this->error('Indica un archivo SQL existente y una base nueva con prefijo creditos_restauracion_.');

            return 1;
        }
        $c = config('database.connections.mysql');
        if ($target === $c['database'] || DB::select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$target])) {
            $this->error('La base destino ya existe o es la de trabajo. No se modificó.');

            return 1;
        }
        $tables = array_map(fn ($row) => array_values((array) $row)[0], DB::select('SHOW TABLES'));
        $fingerprint = function ($connection, $table) {
            $rows = $connection->table($table)->get()->map(fn ($row) => json_encode((array) $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->sort()->values()->all();

            return ['filas' => count($rows), 'sha256' => hash('sha256', implode("\n", $rows))];
        };
        $expected = [];
        foreach ($tables as $table) {
            $expected[$table] = $fingerprint(DB::connection(), $table);
        }
        try {
            DB::statement("CREATE DATABASE `$target` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $p = new Process([
                config('creditos.mysql'),
                '--host='.$c['host'],
                '--port='.$c['port'],
                '--user='.$c['username'],
                $target,
            ], null, ['MYSQL_PWD' => (string) $c['password']]);
            $p->setTimeout(300);
            $stream = fopen($file, 'rb');
            $p->setInput($stream);
            $p->mustRun();
            fclose($stream);
            config(['database.connections.backup_verify' => array_merge($c, ['database' => $target])]);
            $actual = [];
            foreach ($tables as $table) {
                $actual[$table] = $fingerprint(DB::connection('backup_verify'), $table);
            }
            $same = $expected === $actual;
            $report = [
                'fecha' => now()->toIso8601String(),
                'archivo' => $file,
                'destino' => $target,
                'coincide' => $same,
                'origen' => $expected,
                'restaurado' => $actual,
            ];
            file_put_contents(storage_path('app/private/respaldos/verificacion_'.$target.'.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            Log::info('Restauración verificada', $report);
            $this->info($same ? 'Restauración correcta: conteos y SHA-256 coinciden en todas las tablas.' : 'La restauración terminó, pero hay diferencias. Consulta el informe JSON.');
            $this->line('Base de prueba conservada: '.$target);

            return $same ? 0 : 1;
        } catch (\Throwable $e) {
            Log::error('Restauración de prueba fallida', ['base' => $target, 'detalle' => $e->getMessage()]);
            $this->error('No se completó la verificación. La base de trabajo no fue sobrescrita. Revisa el registro local.');

            return 1;
        }
    }
}
