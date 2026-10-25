<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbonoRequest;
use App\Models\Abono;
use App\Models\Credito;
use App\Services\CreditoService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AbonoController extends Controller
{
    public function index(Request $r)
    {
        $d = $r->validate([
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
        ]);
        $q = Abono::with(['credito.cliente', 'usuario']);
        if (! empty($d['desde'])) {
            $q->whereDate('fecha_registro_abono', '>=', $d['desde']);
        }
        if (! empty($d['hasta'])) {
            $q->whereDate('fecha_registro_abono', '<=', $d['hasta']);
        }

        return view('abonos.index', ['abonos' => $q->latest('fecha_registro_abono')->paginate(15)->withQueryString()]);
    }

    public function create(Request $r)
    {
        return view('abonos.create', [
            'creditos' => Credito::with('cliente')->resumen()->orderByDesc('id_credito')->get()->filter(fn ($c) => CreditoService::centavos($c->saldo) > 0),
            'seleccionado' => $r->input('id_credito'),
            'token' => (string) Str::uuid(),
        ]);
    }

    public function store(AbonoRequest $r, CreditoService $s)
    {
        $d = $r->validated();
        $a = $s->abonar(Credito::findOrFail($d['id_credito']), $d['monto_abono'], $d['token'], $r->user()->id);

        return redirect()->route('abonos.show', $a)->with('success', 'Abono aplicado. Puedes imprimir el comprobante.');
    }

    public function show(Abono $abono)
    {
        $abono->load(['credito.cliente', 'usuario', 'cancelador']);

        return view('abonos.show', compact('abono'));
    }

    public function cancel(Request $r, Abono $abono, CreditoService $s)
    {
        $d = $r->validate(['motivo' => 'required|string|min:5|max:500']);
        $s->cancelar($abono, $d['motivo'], $r->user()->id);

        return back()->with('success', 'Abono cancelado. El saldo del crédito se recalculó.');
    }
}
