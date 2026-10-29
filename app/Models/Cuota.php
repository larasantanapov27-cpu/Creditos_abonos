<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('ownedCredit', fn ($query) => $query->whereHas('credito'));
    }

    protected $table = 'cuotas';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['id_credito', 'numero', 'fecha', 'importe'];

    protected $casts = ['fecha' => 'date', 'importe' => 'decimal:2'];

    public function credito()
    {
        return $this->belongsTo(Credito::class, 'id_credito');
    }
}
