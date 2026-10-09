@extends('layouts.app')

@section('title', 'Upload chart - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Upload chart" subtitle="Upload a .zip or .adx chart package." />

    <div class="space-y-6">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert variant="error">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('charts.store') }}" enctype="multipart/form-data"
            class="space-y-6 rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
            @csrf

            {{-- Chart package --}}
            <div>
                <h2 class="text-lg font-bold text-foreground">Chart package</h2>
                <p class="mt-1 text-sm text-foreground-accent">
                    The package must contain a <code>maidata.txt</code>. A <code>bg</code> image,
                    <code>track</code> audio, and <code>pv</code> video in the same directory are detected automatically.
                </p>

                <div class="mt-4">
                    <x-file-input
                        name="package"
                        label="Package"
                        accept=".zip,.adx,application/zip"
                        hint="A .zip or .adx chart package. Max 100MB."
                        required
                    />
                </div>
            </div>

            {{-- Optional overrides --}}
            <div class="border-t border-border pt-6">
                <h2 class="text-lg font-bold text-foreground">Component overrides</h2>
                <p class="mt-1 text-sm text-foreground-accent">
                    Optionally replace the background, track, or PV found in the package.
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <x-file-input
                        name="art"
                        label="Background"
                        accept="image/*"
                        hint="JPEG, PNG, WebP, or GIF. Max 10MB."
                    />
                    <x-file-input
                        name="track"
                        label="Track"
                        accept="audio/*"
                        hint="MP3, OGG, WAV, or M4A. Max 10MB."
                    />
                    <x-file-input
                        name="pv"
                        label="PV"
                        accept="video/mp4,video/webm"
                        hint="MP4 or WebM. Max 30MB."
                    />
                </div>
            </div>

            {{-- Details --}}
            <div class="space-y-6 border-t border-border pt-6">
                <h2 class="text-lg font-bold text-foreground">Details</h2>

                <x-textarea
                    name="description"
                    label="Description"
                    placeholder="Describe your chart... (Markdown supported)"
                    hint="Markdown is supported."
                />

                <label class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        name="is_private"
                        value="1"
                        @checked(old('is_private'))
                        class="h-4 w-4 rounded border-border bg-background text-primary-accent focus:ring-primary-accent/40"
                    >
                    <span class="text-sm text-foreground">
                        Keep this chart private
                        <span class="block text-xs text-foreground-accent">Only you will be able to view it.</span>
                    </span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-border pt-6">
                <x-button href="{{ route('list') }}" variant="secondary" class="w-auto">Cancel</x-button>
                <x-button type="submit" class="w-auto">Upload chart</x-button>
            </div>
        </form>
    </div>
@endsection
