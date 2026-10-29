<?php

namespace App\Models;

use App\Services\CreditoService;
use Illuminate\Database\Eloquent\Model;

class Credito extends Model
{
    use \App\Models\Concerns\OwnedByUser;

    protected static function booted(): void
    {
        static::addGlobalScope('ownedClient', fn ($query) => $query->whereHas('cliente'));
    }

    protected $table = 'registro_creditos';

    protected $primaryKey = 'id_credito';

    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'motivo_credito',
        'monto_credito',
        'fecha_aprobacion',
        'fecha_vencimiento',
        'id_tasa_interes',
        'id_tipo_pago',
        'fecha_pago',
        'cantidad_pagos',
        'periodicidad',
    ];

    protected $casts = [
        'monto_credito' => 'decimal:2',
        'fecha_aprobacion' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class, 'id_credito');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'id_credito')->orderBy('numero');
    }

    public function getTotalAbonadoAttribute(): string
    {
        return CreditoService::decimal($this->abonadoCentavos());
    }

    public function abonadoCentavos(): int
    {
        if (array_key_exists('aplicados_sum', $this->attributes)) {
            return CreditoService::centavos($this->attributes['aplicados_sum'] ?? '0');
        }

        return $this->abonos->whereNull('cancelado_at')->sum(fn ($a) => CreditoService::centavos($a->monto_abono));
    }

    public function getSaldoAttribute(): string
    {
        return CreditoService::decimal(CreditoService::centavos($this->monto_credito) - $this->abonadoCentavos());
    }

    public function getEstadoAttribute(): string
    {
        return CreditoService::centavos($this->saldo) === 0 ? 'Liquidado' : ($this->fecha_vencimiento->lt(today()) ? 'Vencido' : 'Pendiente');
    }

    public function scopeResumen($query)
    {
        return $query->withSum(['abonos as aplicados_sum' => fn ($q) => $q->whereNull('cancelado_at')], 'monto_abono');
    }
}
