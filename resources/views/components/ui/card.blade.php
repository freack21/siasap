@props(['title' => null, 'description' => null, 'padded' => true, 'icon' => null])

<section {{ $attributes->class('card') }}>
    @if ($title || isset($actions))
        <header class="card-header">
            <div class="flex min-w-0 items-center gap-3">
                @if ($icon)
                    <span class="icon-chip">
                        <x-dynamic-component :component="$icon" class="size-4.5" />
                    </span>
                @endif
                <div class="min-w-0">
                    @if ($title)
                        <h2 class="card-title">{{ $title }}</h2>
                    @endif
                    @if ($description)
                        <p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>
                    @endif
                </div>
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
