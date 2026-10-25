@props(['value'])
<span class="status status-{{ match($value) { 'Liquidado','Activo','Aplicado'=>'success','Vencido','Cancelado','Inactivo'=>'danger',default=>'warning' } }}">
    <span></span>
    {{ $value }}
</span>
