@props(['name','label','type'=>'text','value'=>'','required'=>true])
<div {{ $attributes->only('class')->class(['field-group']) }}>
    <label class="form-label" for="{{ $name }}">
        {{ $label }}
        @if($required)
            <span class="text-muted">*</span>
        @endif
    </label>
    <input
        {{ $attributes->except('class') }}
        class="form-control @error($name) is-invalid @enderror"
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type==='password' ? '' : old($name,$value) }}"
        @required($required)
    >
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
