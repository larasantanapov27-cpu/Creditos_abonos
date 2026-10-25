@props(['title','description'=>''])
<div class="page-heading">
    <div>
        <p class="eyebrow">CRÉDITOS Y ABONOS</p>
        <h1>{{ $title }}</h1>
        @if($description)
            <p class="text-muted mb-0">{{ $description }}</p>
        @endif
    </div>
    <div class="heading-actions no-print">{{ $slot }}</div></div>
