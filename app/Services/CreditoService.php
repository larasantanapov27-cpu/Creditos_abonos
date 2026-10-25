<?php

namespace App\Services;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Credito;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditoService
{
    public static function centavos($value): int
    {
        $s = (string) ($value ?? '0');
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $s)) {
            throw ValidationException::withMessages(['monto' => 'El importe debe ser positivo y tener como máximo dos decimales.']);
        }
        [$whole,$fraction] = array_pad(explode('.', $s, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function decimal(int $c): string
    {
        return sprintf('%d.%02d', intdiv($c, 100), abs($c % 100));
    }

    public function calendario(array $d): array
    {
        $c = self::centavos($d['monto_credito']);
        $n = (int) $d['cantidad_pagos'];
        if ($n < 1 || $n > 600 || $c < $n) {
            throw ValidationException::withMessages(['cantidad_pagos' => 'Cada cuota debe ser de al menos $0.01 y el máximo es 600 pagos.']);
        }
        $first = CarbonImmutable::parse($d['fecha_pago']);
        $start = CarbonImmutable::parse($d['fecha_aprobacion']);
        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            $date = match ($d['periodicidad']) {
                'semanal' => $first->addDays(7 * $i), 'quincenal' => $first->addDays(15 * $i), 'mensual' => $first->addMonthsNoOverflow($i), default => throw ValidationException::withMessages(['periodicidad' => 'Periodicidad inválida.'])
            };
            $rows[] = [
                'numero' => $i + 1,
                'fecha' => $date->toDateString(),
                'importe' => self::decimal($i === $n - 1 ? $c - intdiv($c, $n) * ($n - 1) : intdiv($c, $n)),
            ];
        }
        if ($first->lt($start) || end($rows)['fecha'] !== $d['fecha_vencimiento']) {
            throw ValidationException::withMessages([
                'fecha_vencimiento' => 'El primer pago no puede preceder al inicio y el vencimiento debe coincidir con la última cuota. Usa la vista previa.',
            ]);
        }

        return $rows;
    }

    public function guardar(array $d, int $user, ?Credito $credito = null): Credito
    {
        return DB::transaction(function () use ($d, $user, $credito) {
            $cliente = Cliente::whereKey($d['id_cliente'])->lockForUpdate()->firstOrFail();
            if ($credito) {
                $credito = Credito::whereKey($credito->getKey())->lockForUpdate()->firstOrFail();
            }
            if (! $credito || ! $credito->abonos()->exists()) {
                if (! $cliente->activo) {
                    throw ValidationException::withMessages(['id_cliente' => 'No se pueden asignar créditos a clientes inactivos.']);
                }
                $rows = $this->calendario($d);
                if (! $credito) {
                    $credito = new Credito;
                }
                $credito->fill($d);
                $credito->user_id ??= $user;
                $credito->save();
                $credito->cuotas()->delete();
                $credito->cuotas()->createMany($rows);
            } else {
                foreach ([
                    'id_cliente',
                    'monto_credito',
                    'fecha_aprobacion',
                    'fecha_vencimiento',
                    'id_tasa_interes',
                    'id_tipo_pago',
                    'fecha_pago',
                    'cantidad_pagos',
                    'periodicidad',
                ] as $key) {
                    $old = $credito->$key;
                    if ($old instanceof \DateTimeInterface) {
                        $old = $old->format('Y-m-d');
                    }
                    if ($key === 'monto_credito' ? self::centavos($old) !== self::centavos($d[$key]) : (string) $old !== (string) $d[$key]) {
                        throw ValidationException::withMessages([$key => 'Este crédito tiene movimientos. Solo puede modificarse el motivo.']);
                    }
                }
                $credito->update(['motivo_credito' => $d['motivo_credito']]);
            }
            $this->auditar('guardar', 'credito', $credito->getKey(), $user, $d);

            return $credito;
        });
    }

    public function abonar(Credito $credito, string $monto, string $token, int $user): Abono
    {
        return DB::transaction(function () use ($credito, $monto, $token, $user) {
            $credito = Credito::whereKey($credito->getKey())->lockForUpdate()->firstOrFail();
            $existing = Abono::where('token', $token)->first();
            if ($existing) {
                if ($existing->id_credito !== $credito->getKey() || $existing->user_id !== $user || self::centavos($existing->monto_abono) !== self::centavos($monto)) {
                    throw ValidationException::withMessages(['monto_abono' => 'La referencia ya fue utilizada en otra operación.']);
                }

                return $existing;
            }
            $saldo = self::centavos($credito->saldo);
            $c = self::centavos($monto);
            if ($c <= 0 || $c > $saldo) {
                throw ValidationException::withMessages(['monto_abono' => 'El abono debe ser mayor que cero y no superar el saldo actual.']);
            }
            $estado = DB::table('estado_abonos')->where('estado_abono', 'Aplicado')->value('id_estado_abono');
            $abono = $credito->abonos()->create([
                'fecha_registro_abono' => now(),
                'monto_abono' => self::decimal($c),
                'numero_pago' => ((int) $credito->abonos()->max('numero_pago')) + 1,
                'id_estado_abono' => $estado,
                'user_id' => $user,
                'token' => $token,
                'saldo_resultante' => self::decimal($saldo - $c),
            ]);
            $this->auditar('registrar', 'abono', $abono->getKey(), $user, ['monto' => $monto]);

            return $abono;
        });
    }

    public function cancelar(Abono $abono, string $motivo, int $user): void
    {
        DB::transaction(function () use ($abono, $motivo, $user) {
            Credito::whereKey($abono->id_credito)->lockForUpdate()->firstOrFail();
            $abono = Abono::whereKey($abono->getKey())->lockForUpdate()->firstOrFail();
            if ($abono->cancelado_at) {
                throw ValidationException::withMessages(['motivo' => 'El abono ya fue cancelado.']);
            }
            $abono->update([
                'cancelado_at' => now(),
                'cancelado_por' => $user,
                'motivo_cancelacion' => $motivo,
                'id_estado_abono' => DB::table('estado_abonos')->where('estado_abono', 'Cancelado')->value('id_estado_abono'),
            ]);
            $this->auditar('cancelar', 'abono', $abono->getKey(), $user, ['motivo' => $motivo]);
        });
    }

    public function auditar(string $accion, string $entidad, int $id, int $user, array $datos = []): void
    {
        DB::table('auditoria')->insert([
            'accion' => $accion,
            'entidad' => $entidad,
            'registro_id' => $id,
            'user_id' => $user,
            'datos' => json_encode($datos),
            'created_at' => now(),
        ]);
    }
}
