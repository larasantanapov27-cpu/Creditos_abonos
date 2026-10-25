<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registro_clientes')) {
            throw new RuntimeException('Base importada detectada. Ejecuta primero php artisan creditos:adaptar-importacion; consulta README.md. No se borraron datos.');
        }
        Schema::create('estados', function (Blueprint $t) {
            $t->increments('id_estado');
            $t->string('nombre_estado', 50);
        });
        Schema::create('municipios', function (Blueprint $t) {
            $t->increments('id_municipio');
            $t->string('nombre_municipio', 50);
            $t->unsignedInteger('id_estado')->nullable();
            $t->foreign('id_estado')->references('id_estado')->on('estados')->restrictOnDelete();
        });
        Schema::create('tasa_intereses', function (Blueprint $t) {
            $t->increments('id_tasa_interes');
            $t->decimal('tasa', 7, 4);
        });
        Schema::create('tipo_pagos', function (Blueprint $t) {
            $t->increments('id_tipo_pago');
            $t->string('tipo', 25);
        });
        Schema::create('estado_abonos', function (Blueprint $t) {
            $t->increments('id_estado_abono');
            $t->string('estado_abono', 25)->unique();
        });
        Schema::create('registro_clientes', function (Blueprint $t) {
            $t->increments('id_cliente');
            $t->date('fecha_registro_cliente');
            foreach (['nombre', 'apellido_paterno', 'apellido_materno'] as $f) {
                $t->string($f, 25);
            }
            $t->string('telefono', 20)->unique();
            $t->string('correo_electronico', 50);
            $t->unsignedInteger('id_estado');
            $t->unsignedInteger('id_municipio');
            $t->string('codigo_postal', 5);
            $t->string('direccion', 50);
            $t->string('curp', 25)->unique();
            $t->boolean('activo')->default(true)->index();
            $t->index(['nombre', 'apellido_paterno']);
            $t->foreign('id_estado')->references('id_estado')->on('estados')->restrictOnDelete();
            $t->foreign('id_municipio')->references('id_municipio')->on('municipios')->restrictOnDelete();
        });
        Schema::create('registro_creditos', function (Blueprint $t) {
            $t->increments('id_credito');
            $t->unsignedInteger('id_cliente');
            $t->string('motivo_credito', 50);
            $t->decimal('monto_credito', 14, 2);
            $t->date('fecha_aprobacion');
            $t->date('fecha_vencimiento')->index();
            $t->unsignedInteger('id_tasa_interes');
            $t->unsignedInteger('id_tipo_pago');
            $t->date('fecha_pago');
            $t->unsignedSmallInteger('cantidad_pagos');
            $t->string('periodicidad', 15);
            $t->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreign('id_cliente')->references('id_cliente')->on('registro_clientes')->restrictOnDelete();
            $t->foreign('id_tasa_interes')->references('id_tasa_interes')->on('tasa_intereses')->restrictOnDelete();
            $t->foreign('id_tipo_pago')->references('id_tipo_pago')->on('tipo_pagos')->restrictOnDelete();
        });
        Schema::create('cuotas', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('id_credito');
            $t->unsignedSmallInteger('numero');
            $t->date('fecha')->index();
            $t->decimal('importe', 14, 2);
            $t->foreign('id_credito')->references('id_credito')->on('registro_creditos')->cascadeOnDelete();
            $t->unique(['id_credito', 'numero']);
        });
        Schema::create('registro_abonos', function (Blueprint $t) {
            $t->increments('id_abono');
            $t->unsignedInteger('id_credito');
            $t->dateTime('fecha_registro_abono')->index();
            $t->decimal('monto_abono', 14, 2);
            $t->unsignedInteger('numero_pago');
            $t->unsignedInteger('id_estado_abono');
            $t->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $t->uuid('token')->nullable()->unique();
            $t->decimal('saldo_resultante', 14, 2)->nullable();
            $t->text('motivo_cancelacion')->nullable();
            $t->dateTime('cancelado_at')->nullable();
            $t->foreignId('cancelado_por')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreign('id_credito')->references('id_credito')->on('registro_creditos')->restrictOnDelete();
            $t->foreign('id_estado_abono')->references('id_estado_abono')->on('estado_abonos')->restrictOnDelete();
            $t->unique(['id_credito', 'numero_pago']);
        });
        Schema::create('auditoria', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('accion');
            $t->string('entidad');
            $t->unsignedBigInteger('registro_id');
            $t->json('datos')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach ([
            'auditoria',
            'registro_abonos',
            'cuotas',
            'registro_creditos',
            'registro_clientes',
            'estado_abonos',
            'tipo_pagos',
            'tasa_intereses',
            'municipios',
            'estados',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
