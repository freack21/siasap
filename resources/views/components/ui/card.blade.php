@props(['title' => null, 'description' => null, 'padded' => true])

<section {{ $attributes->class('card') }}>
    @if ($title || isset($actions))
        <header class="card-header">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="card-title">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif
    <div @class(['card-body' => $padded])>
        {{ $slot }}
    </div>
</section>
