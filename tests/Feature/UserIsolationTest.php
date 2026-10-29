<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use App\Services\CreditoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $other;
    private Cliente $cliente;
    private array $datos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->actingAs($this->owner);
        $estado = DB::table('estados')->insertGetId(['nombre_estado' => 'Estado']);
        $municipio = DB::table('municipios')->insertGetId(['nombre_municipio' => 'Municipio', 'id_estado' => $estado]);
        $this->cliente = Cliente::create([
            'fecha_registro_cliente' => today(), 'nombre' => 'Privado',
            'apellido_paterno' => 'Propietario', 'apellido_materno' => 'Prueba',
            'telefono' => '5512345678', 'correo_electronico' => 'cliente@example.test',
            'id_estado' => $estado, 'id_municipio' => $municipio,
            'codigo_postal' => '51200', 'direccion' => 'Calle 1',
            'curp' => 'LOGA900101MMCPRN01', 'activo' => true,
        ]);
        $this->datos = [
            'id_cliente' => $this->cliente->getKey(), 'motivo_credito' => 'Privado',
            'monto_credito' => '1000.00', 'fecha_aprobacion' => '2026-01-01',
            'fecha_pago' => '2026-01-31', 'fecha_vencimiento' => '2026-03-31',
            'cantidad_pagos' => 3, 'periodicidad' => 'mensual',
            'id_tasa_interes' => DB::table('tasa_intereses')->value('id_tasa_interes'),
            'id_tipo_pago' => DB::table('tipo_pagos')->value('id_tipo_pago'),
        ];
    }

    public function test_lists_forms_dashboard_and_direct_urls_are_isolated(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 1));
        $service = app(CreditoService::class);
        $credito = $service->guardar($this->datos, $this->owner->id);
        $abono = $service->abonar($credito, '100.00', (string) Str::uuid(), $this->owner->id);
        $this->get('/home')->assertOk()->assertViewHas('stats', fn ($stats) => $stats['monto'] === 100000 && $stats['abonado'] === 10000 && $stats['saldo'] === 90000);
        $this->actingAs($this->other);
        foreach (['/clientes', '/clientes?q=Privado', '/creditos', '/consultas', '/abonos', '/creditos/create', '/abonos/create'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('LOGA900101MMCPRN01')->assertDontSee('Privado Propietario Prueba');
        }
        $this->get('/home')->assertOk()
            ->assertViewHas('stats', fn ($stats) => array_sum($stats) === 0)
            ->assertViewHas('ultimos', fn ($rows) => $rows->isEmpty())
            ->assertViewHas('proximos', fn ($rows) => $rows->isEmpty());
        foreach (['/clientes/'.$this->cliente->getKey(), '/clientes/'.$this->cliente->getKey().'/edit', '/creditos/'.$credito->getKey(), '/creditos/'.$credito->getKey().'/edit', '/abonos/'.$abono->getKey()] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->put('/clientes/'.$this->cliente->getKey(), [])->assertNotFound();
        $this->delete('/clientes/'.$this->cliente->getKey())->assertNotFound();
        $this->patch('/clientes/'.$this->cliente->getKey().'/estado')->assertNotFound();
        $this->put('/creditos/'.$credito->getKey(), $this->datos)->assertNotFound();
        $this->post('/abonos/'.$abono->getKey().'/cancelar', ['motivo' => 'Manipulación de URL'])->assertNotFound();
        $this->post('/creditos', $this->datos)->assertSessionHasErrors('id_cliente');
        $this->post('/creditos/calendario', $this->datos)->assertSessionHasErrors('id_cliente');
        $this->post('/abonos', ['id_credito' => $credito->getKey(), 'monto_abono' => '10.00', 'token' => (string) Str::uuid()])->assertSessionHasErrors('id_credito');
        $this->actingAs($this->owner);
        $this->assertSame('900.00', $credito->fresh()->saldo);
        $this->assertDatabaseHas('registro_abonos', ['id_abono' => $abono->getKey(), 'cancelado_at' => null]);
    }

    public function test_identity_can_repeat_between_accounts_and_owner_cannot_be_posted(): void
    {
        $datos = $this->cliente->only(['nombre', 'apellido_paterno', 'apellido_materno', 'telefono', 'correo_electronico', 'id_estado', 'id_municipio', 'codigo_postal', 'direccion', 'curp']);
        $this->actingAs($this->other);
        $this->post('/clientes', $datos + ['user_id' => $this->owner->id])->assertSessionHasErrors('user_id');
        $this->post('/clientes', $datos)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1, Cliente::count());
        $cliente = Cliente::firstOrFail();
        $this->assertSame($this->other->id, $cliente->user_id);
        $credito = app(CreditoService::class)->guardar(array_replace($this->datos, ['id_cliente' => $cliente->getKey()]), $this->other->id);
        $abono = app(CreditoService::class)->abonar($credito, '50.00', (string) Str::uuid(), $this->other->id);
        $this->assertSame($this->other->id, $abono->user_id);
        $this->actingAs($this->owner)->get('/home')->assertViewHas('stats', fn ($stats) => $stats['monto'] === 0);
    }

    public function test_service_rejects_foreign_records_even_without_route_binding(): void
    {
        $credito = app(CreditoService::class)->guardar($this->datos, $this->owner->id);
        $this->actingAs($this->other);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(CreditoService::class)->abonar($credito, '10.00', (string) Str::uuid(), $this->other->id);
    }

    public function test_legacy_assignment_is_explicit_atomic_and_preserves_finances(): void
    {
        $credito = app(CreditoService::class)->guardar($this->datos, $this->owner->id);
        $abono = app(CreditoService::class)->abonar($credito, '100.00', (string) Str::uuid(), $this->owner->id);
        DB::table('registro_clientes')->where('id_cliente', $this->cliente->getKey())->update(['user_id' => null]);
        $this->get('/clientes/'.$this->cliente->getKey())->assertNotFound();
        $this->get('/creditos/'.$credito->getKey())->assertNotFound();
        $this->get('/abonos/'.$abono->getKey())->assertNotFound();
        $this->artisan('creditos:asignar-propietario', ['cliente' => $this->cliente->getKey(), 'usuario' => $this->owner->id])->assertFailed();
        $this->artisan('creditos:asignar-propietario', ['cliente' => $this->cliente->getKey(), 'usuario' => $this->other->id, '--confirmar' => true])->assertFailed();
        $this->assertDatabaseHas('registro_clientes', ['id_cliente' => $this->cliente->getKey(), 'user_id' => null]);
        $this->artisan('creditos:asignar-propietario', ['cliente' => $this->cliente->getKey(), 'usuario' => $this->owner->id, '--confirmar' => true])->assertSuccessful();
        $this->assertSame('900.00', $credito->fresh()->saldo);
        $this->assertDatabaseCount('registro_clientes', 1);
        $this->assertDatabaseCount('registro_creditos', 1);
        $this->assertDatabaseCount('registro_abonos', 1);
        $this->assertDatabaseHas('auditoria', ['accion' => 'asignar_propietario_cli', 'registro_id' => $this->cliente->getKey()]);
    }

    public function test_legacy_null_owners_are_assigned_only_after_confirmation(): void
    {
        $credito = app(CreditoService::class)->guardar($this->datos, $this->owner->id);
        $abono = app(CreditoService::class)->abonar($credito, '100.00', (string) Str::uuid(), $this->owner->id);
        foreach (['registro_clientes', 'registro_creditos', 'registro_abonos'] as $tabla) {
            DB::table($tabla)->update(['user_id' => null]);
        }
        $this->artisan('creditos:asignar-propietario')->assertSuccessful();
        $this->assertDatabaseHas('registro_abonos', ['id_abono' => $abono->getKey(), 'user_id' => null]);
        $this->artisan('creditos:asignar-propietario', ['cliente' => $this->cliente->getKey(), 'usuario' => $this->other->id, '--confirmar' => true])->assertSuccessful();
        $this->actingAs($this->other);
        $this->assertSame('900.00', $credito->fresh()->saldo);
        $this->assertSame($this->other->id, $abono->fresh()->user_id);
        $this->get('/clientes/'.$this->cliente->getKey())->assertOk();
        $this->actingAs($this->owner)->get('/clientes/'.$this->cliente->getKey())->assertNotFound();
    }

    public function test_service_rejects_foreign_edit_and_cancellation_and_spoofed_actor(): void
    {
        $service = app(CreditoService::class);
        $credito = $service->guardar($this->datos, $this->owner->id);
        $abono = $service->abonar($credito, '100.00', (string) Str::uuid(), $this->owner->id);
        $this->actingAs($this->other);
        foreach ([
            fn () => $service->guardar($this->datos, $this->other->id, $credito),
            fn () => $service->cancelar($abono, 'Operación ajena', $this->other->id),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Se permitió modificar un expediente ajeno.');
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
                $this->assertNotEmpty($exception->getModel());
            }
        }
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->abonar($credito, '10.00', (string) Str::uuid(), $this->owner->id);
    }
}
