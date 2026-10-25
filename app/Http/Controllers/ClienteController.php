<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Services\CreditoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
    public function index(Request $r)
    {
        $q = Cliente::query()->withCount('creditos');
        if ($r->filled('q')) {
            $q->buscar(mb_substr($r->string('q'), 0, 100));
        }
        if (in_array($r->input('activo'), ['0', '1'], true)) {
            $q->where('activo', $r->input('activo'));
        }

        return view('clientes.index', ['clientes' => $q->orderBy('nombre')->paginate(12)->withQueryString()]);
    }

    private function form(Cliente $cliente)
    {
        return view('clientes.form', [
            'cliente' => $cliente,
            'estados' => DB::table('estados')->orderBy('nombre_estado')->get(),
            'municipios' => DB::table('municipios')->orderBy('nombre_municipio')->get(),
        ]);
    }

    public function create()
    {
        return $this->form(new Cliente);
    }

    public function edit(Cliente $cliente)
    {
        return $this->form($cliente);
    }

    public function store(ClienteRequest $r, CreditoService $s)
    {
        $cliente = DB::transaction(function () use ($r, $s) {
            $c = Cliente::create($r->validated() + ['fecha_registro_cliente' => today(), 'activo' => true]);
            $s->auditar('registrar', 'cliente', $c->getKey(), $r->user()->id);

            return $c;
        });

        return redirect()->route('clientes.show', $cliente)->with('success', 'Cliente registrado.');
    }

    public function update(ClienteRequest $r, Cliente $cliente, CreditoService $s)
    {
        DB::transaction(function () use ($r, $cliente, $s) {
            $cliente->update($r->validated());
            $s->auditar('editar', 'cliente', $cliente->getKey(), $r->user()->id);
        });

        return redirect()->route('clientes.show', $cliente)->with('success', 'Datos actualizados.');
    }

    public function show(Cliente $cliente)
    {
        $cliente->load('creditos.abonos');

        return view('clientes.show', compact('cliente'));
    }

    public function toggle(Request $r, Cliente $cliente, CreditoService $s)
    {
        DB::transaction(function () use ($r, $cliente, $s) {
            $c = Cliente::whereKey($cliente->getKey())->lockForUpdate()->firstOrFail();
            $c->update(['activo' => ! $c->activo]);
            $s->auditar($c->activo ? 'reactivar' : 'desactivar', 'cliente', $c->getKey(), $r->user()->id);
        });

        return back()->with('success', 'Estado del cliente actualizado. Su historial se conserva.');
    }

    public function destroy(Request $r, Cliente $cliente, CreditoService $s)
    {
        DB::transaction(function () use ($r, $cliente, $s) {
            $c = Cliente::whereKey($cliente->getKey())->lockForUpdate()->firstOrFail();
            if ($c->creditos()->exists()) {
                throw ValidationException::withMessages(['cliente' => 'El cliente tiene créditos. Puedes desactivarlo para conservar su historial.']);
            } $s->auditar('eliminar', 'cliente', $c->getKey(), $r->user()->id);
            $c->delete();
        });

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }
}
