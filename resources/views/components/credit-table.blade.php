@props(['creditos'])
<p class="d-md-none small text-muted no-print mb-2">Desliza la tabla para ver todas las columnas.</p>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Folio / cliente</th>
                <th>Total pactado</th>
                <th>Abonado</th>
                <th>Saldo pendiente</th>
                <th>Vencimiento</th>
                <th>Estado</th>
                <th>
                    <span class="visually-hidden">Detalle</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($creditos as $c)
                <tr>
                    <td>
                        <a class="fw-semibold" href="{{ route('creditos.show',$c) }}">
                            CR-{{ str_pad($c->id_credito,5,'0',STR_PAD_LEFT) }}
                        </a>
                        <small class="d-block text-muted">
                            {{ $c->cliente->nombre_completo }}
                        </small>
                    </td>
                    <td>
                        ${{ number_format($c->monto_credito,2) }}
                    </td>
                    <td>
                        ${{ number_format($c->total_abonado,2) }}
                    </td>
                    <td class="fw-semibold">
                        ${{ number_format($c->saldo,2) }}
                    </td>
                    <td>
                        {{ $c->fecha_vencimiento->format('d/m/Y') }}
                    </td>
                    <td>
                        <x-status :value="$c->estado" />
                    </td>
                    <td>
                        <a class="btn btn-sm btn-light" href="{{ route('creditos.show',$c) }}">
                            Ver
                            <x-icon name="arrow" />
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">No hay créditos para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
