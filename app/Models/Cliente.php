<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use \App\Models\Concerns\OwnedByUser;

    protected $table = 'registro_clientes';

    protected $primaryKey = 'id_cliente';

    public $timestamps = false;

    protected $fillable = [
        'fecha_registro_cliente',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'correo_electronico',
        'id_estado',
        'id_municipio',
        'codigo_postal',
        'direccion',
        'curp',
        'activo',
    ];

    protected $casts = ['activo' => 'boolean', 'fecha_registro_cliente' => 'date'];

    public function scopeBuscar($query, string $search)
    {
        foreach (array_slice(preg_split('/\s+/', trim($search)), 0, 8) as $term) {
            $query->where(fn ($q) => $q
                ->where('nombre', 'like', "%$term%")
                ->orWhere('apellido_paterno', 'like', "%$term%")
                ->orWhere('apellido_materno', 'like', "%$term%")
                ->orWhere('telefono', 'like', "%$term%")
                ->orWhere('curp', 'like', "%$term%")
                ->orWhere('id_cliente', $term)
            );
        }

        return $query;
    }

    public function creditos()
    {
        return $this->hasMany(Credito::class, 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("$this->nombre $this->apellido_paterno $this->apellido_materno");
    }
}
