<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Cuota;
use App\Services\CreditoService;

class HomeController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'activos' => Cliente::where('activo', true)->count(),
            'Pendiente' => 0,
            'Liquidado' => 0,
            'Vencido' => 0,
            'monto' => 0,
            'abonado' => 0,
            'saldo' => 0,
        ];
        foreach (Credito::resumen()->get() as $c) {
            $stats[$c->estado]++;
            $stats['monto'] += CreditoService::centavos($c->monto_credito);
            $stats['abonado'] += $c->abonadoCentavos();
            $stats['saldo'] += CreditoService::centavos($c->saldo);
        }
        $ultimos = Abono::with(['credito.cliente', 'usuario'])
            ->latest('fecha_registro_abono')
            ->limit(6)
            ->get();

        $proximos = Cuota::with('credito.cliente')
            ->whereDate('fecha', '>=', today())
            ->whereHas('credito', fn ($q) => $q->whereRaw(
                'monto_credito > COALESCE((SELECT SUM(monto_abono) FROM registro_abonos a WHERE a.id_credito = registro_creditos.id_credito AND a.cancelado_at IS NULL),0)'
            ))
            ->whereRaw(
                'COALESCE((SELECT SUM(q.importe) FROM cuotas q WHERE q.id_credito = cuotas.id_credito AND q.numero <= cuotas.numero),0) > COALESCE((SELECT SUM(a.monto_abono) FROM registro_abonos a WHERE a.id_credito = cuotas.id_credito AND a.cancelado_at IS NULL),0)'
            )
            ->orderBy('fecha')
            ->limit(6)
            ->get();

        return view('home', compact('stats', 'ultimos', 'proximos'));
    }
}
