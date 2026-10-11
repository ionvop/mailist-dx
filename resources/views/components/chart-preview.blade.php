{{--
    Lazy Unity WebGL chart preview shown beside the simai code editor.

    The ~32 MB build is only fetched once the user clicks play. The `majdata`
    URL is required (the build reads the chart from it); the media URLs may be
    blank when the chart has no track/art/PV. See resources/js/majdata.js.
--}}
@props([
    'maidata' => null,
    'track' => null,
    'art' => null,
    'pv' => null,
    'level' => null,
])

<div
    class="relative aspect-square overflow-hidden rounded-xl border border-border bg-black"
    x-data="majdataPreview(@js([
        'maidata' => $maidata,
        'track' => $track,
        'art' => $art,
        'pv' => $pv,
        'level' => $level,
    ]))"
>
    {{-- Canvas stage: shown while loading, ready, or after an error. --}}
    <div class="absolute inset-0" x-show="status !== 'idle'" x-cloak aria-hidden="true">
        {{-- The Unity build targets this canvas by id (`#" + Module.canvas.id`), so it must not be blank. --}}
        <canvas id="unity-canvas" x-ref="canvas" class="block h-full w-full bg-black"></canvas>

        <div
            class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/60 p-6 text-center text-sm text-foreground"
            x-show="status === 'loading'"
        >
            <x-lucide-loader-circle class="h-6 w-6 animate-spin text-foreground-accent" />
            <p>Loading preview… <span x-text="progress"></span>%</p>
        </div>

        <div
            class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/60 p-6 text-center text-sm text-foreground"
            x-show="status === 'error'"
            x-cloak
        >
            <x-lucide-triangle-alert class="h-6 w-6 text-destructive" />
            <p class="text-foreground-accent" x-text="error"></p>
            <x-button type="button" variant="secondary" x-on:click="retry()">Try again</x-button>
        </div>
    </div>

    {{-- Opt-in play button: nothing heavy loads until this is clicked. --}}
    <button
        type="button"
        x-show="status === 'idle'"
        x-on:click="start()"
        class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-foreground-accent transition hover:bg-white/5 hover:text-foreground"
    >
        <x-lucide-play class="h-12 w-12" />
        <span class="text-sm font-semibold">Load chart preview</span>
        <span class="text-xs text-foreground-accent">Runs in your browser · ~32 MB</span>
    </button>
</div>
