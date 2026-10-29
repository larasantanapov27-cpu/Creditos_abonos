<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AsignarPropietario extends Command
{
    protected $signature = 'creditos:asignar-propietario {cliente?} {usuario?} {--confirmar}';

    protected $description = 'Lista clientes sin propietario o asigna un expediente completo tras revisión manual';

    public function handle(): int
    {
        if (! $this->argument('cliente') && ! $this->argument('usuario')) {
            DB::table('registro_clientes')->whereNull('user_id')->orderBy('id_cliente')->chunkById(100, function ($clientes) {
                $this->table(['Cliente', 'Nombre'], $clientes->map(fn ($cliente) => [$cliente->id_cliente, $cliente->nombre.' '.$cliente->apellido_paterno])->all());
            }, 'id_cliente');

            return self::SUCCESS;
        }
        if (! $this->argument('cliente') || ! $this->argument('usuario') || ! $this->option('confirmar')) {
            $this->error('Indica cliente y usuario verificados y --confirmar. No se hicieron cambios.');

            return self::FAILURE;
        }
        try {
            DB::transaction(function () {
                $usuario = DB::table('users')->where('id', $this->argument('usuario'))->lockForUpdate()->first();
                $cliente = DB::table('registro_clientes')->where('id_cliente', $this->argument('cliente'))->lockForUpdate()->first();
                if (! $usuario || ! $cliente || $cliente->user_id !== null) {
                    throw new RuntimeException('Usuario inexistente o cliente inexistente/ya asignado.');
                }
                $creditos = DB::table('registro_creditos')->where('id_cliente', $cliente->id_cliente)->lockForUpdate()->get();
                $abonos = DB::table('registro_abonos')->whereIn('id_credito', $creditos->pluck('id_credito'))->lockForUpdate()->get();
                foreach ($creditos->concat($abonos) as $registro) {
                    if ($registro->user_id !== null && (int) $registro->user_id !== (int) $usuario->id) {
                        throw new RuntimeException('Hay propietarios incompatibles en créditos o abonos. Requiere revisión supervisada.');
                    }
                }
                DB::table('registro_clientes')->where('id_cliente', $cliente->id_cliente)->update(['user_id' => $usuario->id]);
                DB::table('registro_creditos')->whereIn('id_credito', $creditos->pluck('id_credito'))->whereNull('user_id')->update(['user_id' => $usuario->id]);
                DB::table('registro_abonos')->whereIn('id_abono', $abonos->pluck('id_abono'))->whereNull('user_id')->update(['user_id' => $usuario->id]);
                DB::table('auditoria')->insert([
                    'user_id' => $usuario->id,
                    'accion' => 'asignar_propietario_cli',
                    'entidad' => 'cliente',
                    'registro_id' => $cliente->id_cliente,
                    'datos' => json_encode(['origen' => 'revision_manual_cli', 'creditos' => $creditos->pluck('id_credito'), 'abonos' => $abonos->pluck('id_abono')]),
                    'created_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            $this->error('Asignación cancelada; transacción revertida: '.$exception->getMessage());

            return self::FAILURE;
        }
        $this->info('Expediente asignado sin modificar importes ni historial financiero.');

        return self::SUCCESS;
    }
}
