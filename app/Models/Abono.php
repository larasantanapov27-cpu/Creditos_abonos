<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('ownedCredit', fn ($query) => $query->whereHas('credito'));
        static::creating(function ($abono) {
            $credito = Credito::findOrFail($abono->id_credito);
            $abono->user_id = $credito->user_id;
        });
        static::updating(function ($abono) {
            Credito::findOrFail($abono->getOriginal('id_credito'));
            $abono->id_credito = $abono->getOriginal('id_credito');
            $abono->user_id = $abono->getOriginal('user_id');
        });
    }

    protected $table = 'registro_abonos';

    protected $primaryKey = 'id_abono';

    public $timestamps = false;

    protected $fillable = [
        'id_credito',
        'fecha_registro_abono',
        'monto_abono',
        'numero_pago',
        'id_estado_abono',
        'token',
        'saldo_resultante',
        'motivo_cancelacion',
        'cancelado_at',
        'cancelado_por',
    ];

    protected $casts = [
        'fecha_registro_abono' => 'datetime',
        'cancelado_at' => 'datetime',
        'monto_abono' => 'decimal:2',
        'saldo_resultante' => 'decimal:2',
    ];

    public function credito()
    {
        return $this->belongsTo(Credito::class, 'id_credito');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cancelador()
    {
        return $this->belongsTo(User::class, 'cancelado_por');
    }
}
