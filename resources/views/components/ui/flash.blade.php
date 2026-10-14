@if (session('message'))
    <div {{ $attributes->class('alert alert-success') }} role="status">
        <x-tabler-circle-check class="size-5 shrink-0" />
        <span>{{ session('message') }}</span>
    </div>
@endif
@if (session('error'))
    <div {{ $attributes->class('alert alert-error') }} role="alert">
        <x-tabler-alert-triangle class="size-5 shrink-0" />
        <span>{{ session('error') }}</span>
    </div>
@endif
