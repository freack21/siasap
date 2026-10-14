@props(['status' => null, 'size' => 'md'])

@php($category = \App\Support\AirQuality::category($status))

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full font-medium ring-1 ring-inset whitespace-nowrap',
    $category['badge'],
    'px-2 py-0.5 text-xs' => $size === 'sm',
    'px-2.5 py-1 text-xs' => $size === 'md',
    'px-3 py-1 text-sm' => $size === 'lg',
]) }}>
    <span class="size-1.5 rounded-full {{ $category['dot'] }}"></span>
    {{ $category['label'] }}
</span>
