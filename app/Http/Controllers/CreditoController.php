<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreditoRequest;
use App\Models\Cliente;
use App\Models\Credito;
use App\Services\CreditoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditoController extends Controller
{
    public function index(Request $r)
    {
        $q = Credito::with('cliente')->resumen();
        if ($r->filled('q')) {
            $s = mb_substr($r->string('q'), 0, 100);
            $q->where(fn ($q) => $q->where('id_credito', $s)->orWhereHas('cliente', fn ($q) => $q->buscar($s)));
        }
        if ($r->filled('id_cliente')) {
            $q->where('id_cliente', $r->input('id_cliente'));
        }
        $balance = 'monto_credito - COALESCE((SELECT SUM(monto_abono) FROM registro_abonos a WHERE a.id_credito = registro_creditos.id_credito AND a.cancelado_at IS NULL),0)';
        if ($r->estado === 'Liquidado') {
            $q->whereRaw("($balance) = 0");
        }
        if (in_array($r->estado, ['Pendiente', 'Vencido'])) {
            $q->whereRaw("($balance) > 0");
            $q->whereDate('fecha_vencimiento', $r->estado === 'Vencido' ? '<' : '>=', today());
        }

        return view('creditos.index', ['creditos' => $q->latest('id_credito')->paginate(12)->withQueryString()]);
    }

    private function form(Credito $credito)
    {
        return view('creditos.form', [
            'credito' => $credito,
            'clientes' => Cliente::where(fn ($query) => $query->where('activo', true)->when($credito->exists, fn ($query) => $query->orWhere('id_cliente', $credito->id_cliente)))->orderBy('nombre')->get(),
            'tasas' => DB::table('tasa_intereses')->get(),
            'tipos' => DB::table('tipo_pagos')->get(),
            'bloqueado' => $credito->exists && $credito->abonos()->exists(),
        ]);
    }

    public function create()
    {
        return $this->form(new Credito);
    }

    public function edit(Credito $credito)
    {
        return $this->form($credito);
    }

    public function store(CreditoRequest $r, CreditoService $s)
    {
        $c = $s->guardar($r->validated(), $r->user()->id);

        return redirect()->route('creditos.show', $c)->with('success', 'Crédito registrado y calendario generado.');
    }

    public function update(CreditoRequest $r, Credito $credito, CreditoService $s)
    {
        $c = $s->guardar($r->validated(), $r->user()->id, $credito);

        return redirect()->route('creditos.show', $c)->with('success', 'Crédito actualizado.');
    }

    public function show(Credito $credito)
    {
        $credito->load(['cliente', 'abonos.usuario', 'cuotas']);

        return view('creditos.show', compact('credito'));
    }

    public function preview(CreditoRequest $r, CreditoService $s)
    {
        return response()->json($s->calendario($r->validated()));
    }
}
