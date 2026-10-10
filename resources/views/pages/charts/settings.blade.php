@extends('layouts.app')

@section('title', 'Chart settings - '.config('app.name', 'mailist DX'))

@section('content')
    @include('pages.charts._header', ['chart' => $chart, 'activeTab' => 'settings'])

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

        {{-- Metadata --}}
        <form method="POST" action="{{ route('charts.settings.update', $chart) }}"
            class="space-y-6 rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
            @csrf
            @method('PATCH')

            <div>
                <h2 class="text-lg font-bold text-foreground">Chart details</h2>
                <p class="mt-1 text-sm text-foreground-accent">
                    These values are written back to the chart's <code>maidata.txt</code> and its package.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="title" label="Title" :value="$chart->title" required />
                <x-input name="title_alias" label="Title alias" :value="$chart->title_alias"
                    hint="Alternative title(s), comma-separated." />
                <x-input name="artist" label="Artist" :value="$chart->artist" required />
                <x-input name="artist_alias" label="Artist alias" :value="$chart->artist_alias"
                    hint="Alternative artist name(s), comma-separated." />
                <x-input name="artist_link" label="Artist link" type="url" :value="$chart->artist_link"
                    placeholder="https://..." />
                <x-input name="illustrator" label="Illustrator" :value="$chart->illustrator" />
                <x-input name="illustrator_link" label="Illustrator link" type="url" :value="$chart->illustrator_link"
                    placeholder="https://..." />
                <x-input name="note_designer" label="Note designer" :value="$chart->note_designer"
                    hint="Used as the fallback charter for each difficulty." />
                <x-input name="audio_bpm" label="BPM" :value="$chart->audio_bpm" inputmode="decimal" />
                <x-input name="tags" label="Tags" :value="$chart->tags ? implode(', ', $chart->tags) : ''"
                    hint="Comma-separated." />
            </div>

            <x-textarea name="info" label="Level info" :value="$chart->info"
                hint="Free-form information shown for the level (&freemsg=)." />
            <x-textarea name="description" label="Description" :value="$chart->description"
                placeholder="Describe your chart... (Markdown supported)" hint="Markdown is supported." />

            <div class="flex items-center justify-end gap-3 border-t border-border pt-6">
                <x-button type="submit" class="w-auto">Save details</x-button>
            </div>
        </form>

        {{-- Assets --}}
        <form method="POST" action="{{ route('charts.settings.assets', $chart) }}" enctype="multipart/form-data"
            class="space-y-6 rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
            @csrf
            @method('PATCH')

            <div>
                <h2 class="text-lg font-bold text-foreground">Assets</h2>
                <p class="mt-1 text-sm text-foreground-accent">
                    Replace the chart background, track, or PV. Leave a field empty to keep the current file.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="space-y-2">
                    <x-file-input name="art" label="Background" accept="image/*"
                        hint="JPEG, PNG, WebP, or GIF. Max 10MB." />
                    @if ($chart->art_path)
                        <p class="text-xs text-foreground-accent">
                            Current:
                            <a href="{{ route('charts.files', [$chart, 'art']) }}" target="_blank"
                                class="font-medium text-primary-accent hover:underline">{{ basename($chart->art_path) }}</a>
                        </p>
                    @else
                        <p class="text-xs text-foreground-accent">No background set.</p>
                    @endif
                </div>

                <div class="space-y-2">
                    <x-file-input name="track" label="Track" accept="audio/*"
                        hint="MP3, OGG, WAV, or M4A. Max 10MB." />
                    @if ($chart->track_path)
                        <p class="text-xs text-foreground-accent">
                            Current:
                            <a href="{{ route('charts.files', [$chart, 'track']) }}" target="_blank"
                                class="font-medium text-primary-accent hover:underline">{{ basename($chart->track_path) }}</a>
                        </p>
                    @else
                        <p class="text-xs text-foreground-accent">No track set.</p>
                    @endif
                </div>

                <div class="space-y-2">
                    <x-file-input name="pv" label="PV" accept="video/mp4,video/webm"
                        hint="MP4 or WebM. Max 30MB." />
                    @if ($chart->pv_path)
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="remove_pv" value="1" @checked(old('remove_pv'))
                                class="mt-0.5 h-4 w-4 rounded border-border bg-background text-primary-accent focus:ring-primary-accent/40">
                            <span class="text-xs text-foreground-accent">
                                Remove the current PV
                                (<a href="{{ route('charts.files', [$chart, 'pv']) }}" target="_blank"
                                    class="font-medium text-primary-accent hover:underline">{{ basename($chart->pv_path) }}</a>).
                                Uploading a new PV overrides this.
                            </span>
                        </label>
                    @else
                        <p class="text-xs text-foreground-accent">No PV set.</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-border pt-6">
                <x-button type="submit" class="w-auto">Save assets</x-button>
            </div>
        </form>

        {{-- Edit access --}}
        <div class="space-y-6 rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
            <div>
                <h2 class="text-lg font-bold text-foreground">Edit access</h2>
                <p class="mt-1 text-sm text-foreground-accent">
                    Give other users edit access to this chart. They can view private charts and edit its charts.
                </p>
            </div>

            @if ($chart->collaborators->isEmpty())
                <p class="rounded-lg border border-dashed border-border bg-background px-4 py-6 text-center text-sm text-foreground-accent">
                    No one else has edit access yet.
                </p>
            @else
                <ul class="divide-y divide-border rounded-lg border border-border bg-background">
                    @foreach ($chart->collaborators as $collaborator)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="flex min-w-0 items-center gap-3">
                                @if ($collaborator->avatarUrl)
                                    <img src="{{ $collaborator->avatarUrl }}" alt="{{ $collaborator->username }}"
                                        class="h-8 w-8 shrink-0 rounded-full object-cover">
                                @else
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-background-accent text-xs font-bold text-foreground-accent">
                                        {{ $collaborator->initial() }}
                                    </span>
                                @endif
                                <span class="truncate text-sm font-medium text-foreground">{{ $collaborator->username }}</span>
                            </div>

                            <form method="POST"
                                action="{{ route('charts.settings.collaborators.destroy', [$chart, $collaborator]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background-accent px-3 py-1.5 text-xs font-semibold text-destructive transition hover:bg-destructive/10">
                                    <x-lucide-x class="h-3.5 w-3.5" /> Remove
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('charts.settings.collaborators.store', $chart) }}"
                class="flex flex-col gap-4 border-t border-border pt-6 sm:flex-row sm:items-end">
                @csrf

                <div class="min-w-0 flex-1">
                    <x-input name="username" label="Username" :value="old('username')"
                        placeholder="e.g. chartmaster" hint="The user must already have an account." />
                </div>

                <x-button type="submit" class="w-auto shrink-0">
                    <x-lucide-user-plus class="h-4 w-4" /> Add
                </x-button>
            </form>
        </div>

        {{-- Visibility --}}
        <form method="POST" action="{{ route('charts.settings.visibility', $chart) }}"
            class="space-y-6 rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
            @csrf
            @method('PATCH')

            <div>
                <h2 class="text-lg font-bold text-foreground">Visibility</h2>
                <p class="mt-1 text-sm text-foreground-accent">Control who can find and view this chart.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="status" class="block text-sm font-medium text-foreground">Status</label>
                    <select name="status" id="status"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-primary-accent focus:ring-2 focus:ring-primary-accent/40">
                        @foreach (\App\Enums\ChartStatus::cases() as $status)
                            @if ($status === \App\Enums\ChartStatus::Approved && $chart->status !== \App\Enums\ChartStatus::Approved)
                                @continue
                            @endif
                            <option value="{{ $status->value }}" @selected(old('status', $chart->status->value) === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="text-xs text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-start gap-3 sm:pt-8">
                    <input type="checkbox" name="is_private" value="1" @checked(old('is_private', $chart->is_private))
                        class="mt-0.5 h-4 w-4 rounded border-border bg-background text-primary-accent focus:ring-primary-accent/40">
                    <span class="text-sm text-foreground">
                        Keep this chart private
                        <span class="block text-xs text-foreground-accent">Only you will be able to view it.</span>
                    </span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-border pt-6">
                <x-button type="submit" class="w-auto">Save visibility</x-button>
            </div>
        </form>

        {{-- Danger zone --}}
        <div class="rounded-2xl border border-destructive/40 bg-destructive/5 p-6 sm:p-8" x-data="{ confirm: false }">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/15">
                    <x-lucide-alert-triangle class="h-5 w-5 text-destructive" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-foreground">Delete chart</h2>
                    <p class="mt-1 text-sm text-foreground-accent">
                        Permanently delete this chart, its difficulties, and all of its files. This cannot be undone.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" @click="confirm = true"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-destructive/50 bg-background px-4 py-2.5 text-sm font-semibold text-destructive transition hover:bg-destructive/10">
                    <x-lucide-trash-2 class="h-4 w-4" /> Delete chart
                </button>
            </div>

            {{-- Confirmation modal --}}
            <div x-show="confirm" x-cloak x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                @keydown.escape.window="confirm = false">
                <div class="w-full max-w-md rounded-2xl border border-border bg-background p-6 shadow-2xl" @click.outside="confirm = false">
                    <h2 class="text-lg font-bold text-foreground">Delete “{{ $chart->title }}”?</h2>
                    <p class="mt-1 text-sm text-foreground-accent">
                        This permanently removes the chart and all of its files. This cannot be undone.
                    </p>

                    <form method="POST" action="{{ route('charts.destroy', $chart) }}" class="mt-6 flex items-center justify-end gap-3">
                        @csrf
                        @method('DELETE')

                        <button type="button" @click="confirm = false"
                            class="inline-flex items-center justify-center rounded-lg border border-border bg-background-accent px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-accent">
                            Cancel
                        </button>
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-destructive px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110">
                            Delete chart
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
