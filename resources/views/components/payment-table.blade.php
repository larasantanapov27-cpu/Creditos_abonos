@props(['abonos'])
<p class="d-md-none small text-muted no-print mb-2">Desliza la tabla para ver todas las columnas.</p>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Comprobante</th>
                <th>Fecha y hora</th>
                <th>Cliente / crédito</th>
                <th>Importe</th>
                <th>Estado</th>
                <th>Registró</th>
            </tr>
        </thead>
        <tbody>
            @forelse($abonos as $a)
                <tr>
                    <td>
                        <a href="{{ route('abonos.show',$a) }}">
                            AB-{{ str_pad($a->id_abono,5,'0',STR_PAD_LEFT) }}
                        </a>
                        <small class="d-block text-muted">
                            Abono n.º {{ $a->numero_pago }}
                        </small>
                    </td>
                    <td>
                        {{ $a->fecha_registro_abono->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        {{ $a->credito->cliente->nombre_completo }}
                        <small class="d-block">
                            <a href="{{ route('creditos.show',$a->credito) }}">
                                CR-{{ str_pad($a->id_credito,5,'0',STR_PAD_LEFT) }}
                            </a>
                        </small>
                    </td>
                    <td class="fw-semibold">
                        ${{ number_format($a->monto_abono,2) }}
                    </td>
                    <td>
                        <x-status :value="$a->cancelado_at ? 'Cancelado' : 'Aplicado'" />
                    </td>
                    <td>
                        {{ $a->usuario?->name ?? 'Registro anterior' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-state">Todavía no hay abonos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
