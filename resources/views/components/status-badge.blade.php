@props([
    'status',
])

<span
    {{ $attributes->class(['inline-flex items-center rounded-full border bg-background/80 px-2.5 py-0.5 text-[11px] font-semibold backdrop-blur', $status->colorClasses()]) }}>
    {{ $status->label() }}
</span>
