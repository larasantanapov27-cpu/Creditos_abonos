<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class Respaldar extends Command
{
    protected $signature = 'creditos:respaldar';

    protected $description = 'Respalda MariaDB fuera de public y registra el resultado';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('Este comando requiere la conexión mysql/MariaDB.');

            return 1;
        }
        $c = config('database.connections.mysql');
        $dir = storage_path('app/private/respaldos');
        File::ensureDirectoryExists($dir);
        $path = $dir.'/creditos_'.now()->format('Ymd_His_u').'.sql';
        try {
            $p = new Process([
                config('creditos.mysqldump'),
                '--host='.$c['host'],
                '--port='.$c['port'],
                '--user='.$c['username'],
                '--single-transaction',
                '--routines',
                '--triggers',
                '--skip-comments',
                '--result-file='.$path,
                $c['database'],
            ], null, ['MYSQL_PWD' => (string) $c['password']]);
            $p->setTimeout(300);
            $p->mustRun();
            if (! is_file($path) || filesize($path) === 0) {
                throw new \RuntimeException('Archivo vacío.');
            }
            Log::info('Respaldo completado', ['archivo' => $path]);
            $this->info($path);

            return 0;
        } catch (\Throwable $e) {
            if (is_file($path)) {
                unlink($path);
            } Log::error('Respaldo fallido', ['detalle' => $e->getMessage()]);
            $this->error('No se pudo crear el respaldo. Revisa storage/logs/laravel.log.');

            return 1;
        }
    }
}
