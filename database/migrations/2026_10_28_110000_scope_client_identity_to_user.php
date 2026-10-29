<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Schema::getIndexes('registro_clientes') as $index) {
            if ($index['unique'] && in_array($index['columns'], [['telefono'], ['curp']], true)) {
                Schema::table('registro_clientes', fn (Blueprint $table) => $table->dropUnique($index['name']));
            }
        }
        Schema::table('registro_clientes', function (Blueprint $table) {
            $table->unique(['user_id', 'telefono'], 'clientes_usuario_telefono_unique');
            $table->unique(['user_id', 'curp'], 'clientes_usuario_curp_unique');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('La reversión requiere revisar duplicados entre cuentas. No se modificaron datos.');
    }
};
