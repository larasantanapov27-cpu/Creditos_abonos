<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\User;
use App\Services\CreditoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreditosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Cliente $cliente;

    private array $datos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::create([
            'name' => 'Prueba',
            'username' => 'prueba',
            'email' => 'prueba@example.test',
            'password' => 'ClaveSegura2026!',
        ]);
        $e = DB::table('estados')->insertGetId(['nombre_estado' => 'Estado de México']);
        $m = DB::table('municipios')->insertGetId(['nombre_municipio' => 'Valle de Bravo', 'id_estado' => $e]);
        $this->actingAs($this->user);
        $this->cliente = Cliente::create([
            'fecha_registro_cliente' => '2026-01-01',
            'nombre' => 'Ana',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'García',
            'telefono' => '5512345678',
            'correo_electronico' => 'ana@example.test',
            'id_estado' => $e,
            'id_municipio' => $m,
            'codigo_postal' => '51200',
            'direccion' => 'Calle 1',
            'curp' => 'LOGA900101MMCPRN01',
            'activo' => true,
        ]);
        $this->datos = [
            'id_cliente' => $this->cliente->id_cliente,
            'motivo_credito' => 'Prueba de crédito',
            'monto_credito' => '1000.00',
            'fecha_aprobacion' => '2026-01-01',
            'fecha_pago' => '2026-01-31',
            'fecha_vencimiento' => '2026-03-31',
            'cantidad_pagos' => 3,
            'periodicidad' => 'mensual',
            'id_tasa_interes' => DB::table('tasa_intereses')->value('id_tasa_interes'),
            'id_tipo_pago' => DB::table('tipo_pagos')->value('id_tipo_pago'),
        ];
        auth()->logout();
    }

    private function credito(): Credito
    {
        $this->actingAs($this->user);
        return app(CreditoService::class)->guardar($this->datos, $this->user->id);
    }

    public function test_protected_routes_require_session(): void
    {
        foreach (['/home', '/clientes', '/creditos', '/abonos', '/consultas', '/catalogos'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_registration_login_logout_and_password_hash(): void
    {
        $this->post('/registro', [
            'name' => 'Nueva',
            'username' => 'nueva',
            'email' => 'nueva@example.test',
            'password' => 'OtraClave2026!',
            'password_confirmation' => 'OtraClave2026!',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('OtraClave2026!', User::where('username', 'nueva')->first()->password));
        $this->post('/login', ['username' => 'nueva', 'password' => 'incorrecta'])->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'nueva', 'password' => 'OtraClave2026!'])->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/home')->assertRedirect(route('login'));
    }

    public function test_login_attempts_are_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $r = $this->post('/login', ['username' => 'intentos', 'password' => 'incorrecta']);
        }$r->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_unique_registration(): void
    {
        $this->post('/registro', [
            'name' => 'X',
            'username' => 'prueba',
            'email' => 'prueba@example.test',
            'password' => 'ClaveSegura2026!',
            'password_confirmation' => 'ClaveSegura2026!',
        ])->assertSessionHasErrors(['username', 'email']);
    }

    public function test_client_creation_and_state_validation(): void
    {
        $d = $this->cliente->only([
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
        ]);
        $d['telefono'] = '5587654321';
        $d['curp'] = 'LOGA900101MMCPRN02';
        $this->actingAs($this->user)->post('/clientes', $d)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registro_clientes', ['telefono' => '5587654321']);
        $e = DB::table('estados')->insertGetId(['nombre_estado' => 'Otro estado']);
        $d['id_estado'] = $e;
        $this->post('/clientes', $d)->assertSessionHasErrors('id_municipio');
    }

    public function test_client_deactivation_blocks_new_credit_but_allows_payment(): void
    {
        $c = $this->credito();
        $this->actingAs($this->user)->patch('/clientes/'.$this->cliente->id_cliente.'/estado')->assertSessionHasNoErrors();
        $this->assertFalse($this->cliente->fresh()->activo);
        $this->post('/creditos', $this->datos)->assertSessionHasErrors('id_cliente');
        $this->post('/abonos', ['id_credito' => $c->id_credito, 'monto_abono' => '10.00', 'token' => (string) Str::uuid()])->assertSessionHasNoErrors();
        $this->delete('/clientes/'.$this->cliente->id_cliente)->assertSessionHasErrors('cliente');
        $this->patch('/clientes/'.$this->cliente->id_cliente.'/estado')->assertSessionHasNoErrors();
        $this->assertTrue($this->cliente->fresh()->activo);
    }

    public function test_month_end_calendar_and_exact_rounding(): void
    {
        $rows = app(CreditoService::class)->calendario($this->datos);
        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31'], array_column($rows, 'fecha'));
        $this->assertSame(['333.33', '333.33', '333.34'], array_column($rows, 'importe'));
        $c = $this->credito();
        $this->assertCount(3, $c->cuotas);
    }

    public function test_home_omits_fully_covered_future_installments(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));
        $c = $this->credito();
        app(CreditoService::class)->abonar($c, '333.33', (string) Str::uuid(), $this->user->id);
        $this->actingAs($this->user)->get('/home')->assertOk()->assertViewHas('proximos', fn ($rows) => $rows->pluck('numero')->all() === [2, 3]);
    }

    public function test_weekly_and_fortnightly_calendar(): void
    {
        foreach (['semanal' => ['2026-02-07', '2026-02-14'], 'quincenal' => ['2026-02-15', '2026-03-02']] as $period => $dates) {
            $d = $this->datos;
            $d['periodicidad'] = $period;
            $d['fecha_vencimiento'] = $dates[1];
            $r = app(CreditoService::class)->calendario($d);
            $this->assertSame($dates, array_slice(array_column($r, 'fecha'), 1));
        }
    }

    public function test_calendar_rejects_inconsistent_due_date(): void
    {
        $d = $this->datos;
        $d['fecha_vencimiento'] = '2026-03-30';
        $this->expectException(ValidationException::class);
        app(CreditoService::class)->calendario($d);
    }

    public function test_credit_editing_and_financial_lock(): void
    {
        $c = $this->credito();
        $d = $this->datos;
        $d['monto_credito'] = '1200.00';
        $this->actingAs($this->user)->put('/creditos/'.$c->id_credito, $d)->assertSessionHasNoErrors();
        $this->assertSame('1200.00', $c->fresh()->monto_credito);
        app(CreditoService::class)->abonar($c, '100.00', (string) Str::uuid(), $this->user->id);
        $d['monto_credito'] = '1300.00';
        $this->put('/creditos/'.$c->id_credito, $d)->assertSessionHasErrors('monto_credito');
        $d['monto_credito'] = '1200.00';
        $d['motivo_credito'] = 'Motivo corregido';
        $this->put('/creditos/'.$c->id_credito, $d)->assertSessionHasNoErrors();
        $this->assertSame('Motivo corregido', $c->fresh()->motivo_credito);
    }

    public function test_partial_exact_payment_cancellation_and_reopening(): void
    {
        $s = app(CreditoService::class);
        $c = $this->credito();
        $a = $s->abonar($c, '333.33', (string) Str::uuid(), $this->user->id);
        $this->assertSame('666.67', $c->fresh()->saldo);
        $b = $s->abonar($c, '666.67', (string) Str::uuid(), $this->user->id);
        $this->assertSame('Liquidado', $c->fresh()->estado);
        $s->cancelar($b, 'Corrección de captura', $this->user->id);
        $this->assertSame('666.67', $c->fresh()->saldo);
        $this->assertNotNull($b->fresh()->cancelado_at);
        $this->assertSame(2, Abono::count());
        $this->assertSame('Cancelado', DB::table('estado_abonos')->where('id_estado_abono', $b->fresh()->id_estado_abono)->value('estado_abono'));
    }

    public function test_invalid_payments_are_rejected(): void
    {
        $c = $this->credito();
        foreach (['0', '-10', '1000.01', '1.001'] as $monto) {
            $this->actingAs($this->user)->post('/abonos', ['id_credito' => $c->id_credito, 'monto_abono' => $monto, 'token' => (string) Str::uuid()])->assertSessionHasErrors('monto_abono');
        }$this->assertSame(0, Abono::count());
    }

    public function test_liquidated_credit_rejects_more_payments(): void
    {
        $s = app(CreditoService::class);
        $c = $this->credito();
        $s->abonar($c, '1000.00', (string) Str::uuid(), $this->user->id);
        $this->expectException(ValidationException::class);
        $s->abonar($c, '0.01', (string) Str::uuid(), $this->user->id);
    }

    public function test_duplicate_token_is_idempotent_and_consecutive_is_unique(): void
    {
        $s = app(CreditoService::class);
        $c = $this->credito();
        $t = (string) Str::uuid();
        $a = $s->abonar($c, '100.00', $t, $this->user->id);
        $b = $s->abonar($c, '100.00', $t, $this->user->id);
        $this->assertSame($a->id_abono, $b->id_abono);
        $this->assertSame(1, Abono::count());
        $this->assertSame('900.00', $c->fresh()->saldo);
        $x = $s->abonar($c, '10.00', (string) Str::uuid(), $this->user->id);
        $this->assertSame(2, $x->numero_pago);
    }

    public function test_duplicate_token_with_changed_amount_is_rejected(): void
    {
        $s = app(CreditoService::class);
        $c = $this->credito();
        $t = (string) Str::uuid();
        $s->abonar($c, '100.00', $t, $this->user->id);
        $this->expectException(ValidationException::class);
        $s->abonar($c, '200.00', $t, $this->user->id);
    }

    public function test_overdue_and_pending_are_derived_from_balance_and_date(): void
    {
        $this->travelTo(Carbon::parse('2026-03-31 12:00:00'));
        $c = $this->credito();
        $this->assertSame('Pendiente', $c->estado);
        $this->travelTo(Carbon::parse('2026-04-01 00:00:00'));
        $this->assertSame('Vencido', $c->fresh()->estado);
    }

    public function test_receipt_histories_filters_and_all_views_render(): void
    {
        $c = $this->credito();
        $a = app(CreditoService::class)->abonar($c, '100.00', (string) Str::uuid(), $this->user->id);
        $this->actingAs($this->user);
        foreach ([
            '/home',
            '/clientes',
            '/clientes/create',
            '/clientes/'.$this->cliente->id_cliente,
            '/clientes/'.$this->cliente->id_cliente.'/edit',
            '/creditos',
            '/creditos/create',
            '/creditos/'.$c->id_credito,
            '/creditos/'.$c->id_credito.'/edit',
            '/abonos',
            '/abonos/create',
            '/abonos/'.$a->id_abono,
            '/consultas',
            '/catalogos',
            '/catalogos?tipo=municipios',
            '/catalogos?tipo=tasa_intereses',
            '/catalogos?tipo=tipo_pagos',
        ] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/abonos/'.$a->id_abono)->assertSee('100.00')->assertSee('900.00');
        $this->get('/clientes?q=Ana%20L%C3%B3pez%20Garc%C3%ADa')->assertSee('LOGA900101MMCPRN01');
        $this->get('/creditos?q=Ana%20L%C3%B3pez%20Garc%C3%ADa')->assertSee('CR-00001');
        $this->get('/creditos?estado=Liquidado')->assertDontSee('CR-00001');
        $this->get('/abonos?desde=2099-01-01&hasta=2099-12-31')->assertSee('Todavía no hay abonos');
    }

    public function test_catalogs_protect_used_records_and_internal_states(): void
    {
        $this->actingAs($this->user)->delete('/catalogos/estados/'.$this->cliente->id_estado)->assertSessionHasErrors('catalogo');
        $this->post('/catalogos/estado_abonos', ['valor' => 'Otro'])->assertNotFound();
    }

    public function test_guest_views_use_local_assets_without_vite_or_cdn(): void
    {
        foreach (['/login', '/registro'] as $url) {
            $r = $this->get($url);
            $r->assertOk()->assertSee('/bootstrap/css/bootstrap.min.css', false)->assertSee('/bootstrap/js/bootstrap.bundle.min.js', false)->assertSee('/css/app.css', false)->assertDontSee('@vite')->assertDontSee('fonts.googleapis.com')->assertDontSee('cdn.jsdelivr.net');
        }
    }
}
