@extends('layouts.app')

@section('title', 'Settings - '.config('app.name', 'mailist DX'))

@section('content')
    <x-page-header title="Settings" subtitle="Manage your account preferences." />

    <div class="space-y-6">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @if ($currentUser)
            <div class="rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8"
                x-data="profileForm(@js($currentUser->username))">
                <h2 class="text-lg font-bold text-foreground">Profile</h2>
                <p class="mt-1 text-sm text-foreground-accent">Your avatar, username, and markdown-supported bio.</p>

                @if ($errors->any())
                    <x-alert variant="error" class="mt-4">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif

                <form x-ref="form" method="POST" action="{{ route('settings.profile.update') }}"
                    enctype="multipart/form-data" class="mt-6 space-y-6" @submit.prevent="submit($event)">
                    @csrf
                    @method('PATCH')

                    {{-- Avatar --}}
                    <div x-data="{ preview: @js($currentUser->avatarUrl) }">
                        <label class="block text-sm font-medium text-foreground">Avatar</label>

                        <div class="mt-2 flex items-center gap-4">
                            <template x-if="preview">
                                <img :src="preview" alt="Avatar preview"
                                    class="h-20 w-20 shrink-0 rounded-full border border-border object-cover">
                            </template>
                            <template x-if="!preview">
                                <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-primary/20 text-2xl font-bold text-primary-accent">
                                    {{ $currentUser->initial() }}
                                </span>
                            </template>

                            <div class="min-w-0 flex-1">
                                <x-file-input
                                    name="avatar"
                                    compact
                                    hint="JPEG, PNG, WebP, or GIF. Max 2MB."
                                    @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); }"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- Username --}}
                    <div x-data="usernameCooldown({{ $canChangeUsername ? 'true' : 'false' }}, @js($usernameAvailableAt))">
                        <label for="username" class="block text-sm font-medium text-foreground">Username</label>

                        <input
                            type="text"
                            name="username"
                            id="username"
                            value="{{ old('username', $currentUser->username) }}"
                            :disabled="!canChange"
                            minlength="3"
                            maxlength="30"
                            autocomplete="username"
                            class="mt-1.5 w-full rounded-lg border bg-background px-3 py-2 text-sm text-foreground placeholder:text-foreground-accent/60 outline-none transition focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50 {{ $errors->has('username') ? 'border-destructive focus:border-destructive focus:ring-destructive/40' : 'border-border focus:border-primary-accent focus:ring-primary-accent/40' }}"
                        >

                        <p class="mt-1 text-xs text-foreground-accent">Letters, numbers, hyphens, and underscores only.</p>

                        <template x-if="!canChange">
                            <p class="mt-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-300">
                                You can change your username again in
                                <span x-text="remaining" class="font-semibold"></span>.
                            </p>
                        </template>

                        @error('username')
                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Bio --}}
                    <x-textarea
                        name="bio"
                        label="Bio"
                        :value="$currentUser->bio"
                        placeholder="Tell people about yourself... (Markdown supported)"
                        hint="Markdown is supported."
                    />

                    <x-button type="submit">Save changes</x-button>
                </form>

                {{-- Username change confirmation modal --}}
                <div x-show="showConfirm" x-cloak x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                    @keydown.escape.window="cancel()">
                    <div class="w-full max-w-md rounded-2xl border border-border bg-background p-6 shadow-2xl" @click.outside="cancel()">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-500/15">
                                <x-lucide-alert-triangle class="h-5 w-5 text-amber-400" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-foreground">Change username?</h2>
                                <p class="mt-1 text-sm text-foreground-accent">
                                    Changing your username will start a
                                    <span class="font-semibold text-foreground">{{ \App\Models\User::USERNAME_CHANGE_COOLDOWN_MINUTES }}-minute cooldown</span>
                                    before you can change it again.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <button type="button" @click="cancel()"
                                class="inline-flex items-center justify-center rounded-lg border border-border bg-background-accent px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-accent">
                                Cancel
                            </button>
                            <button type="button" @click="confirmSubmit()"
                                class="inline-flex items-center justify-center rounded-lg bg-primary-gradient px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/20 transition hover:brightness-110">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <x-placeholder title="Profile" description="Sign in to edit your avatar, username, and bio." icon="file-text" />
        @endif

        <x-placeholder title="Account" description="Username, email address, and password." icon="user" />
        <x-placeholder title="Notifications" description="Choose which activity sends you a notification." icon="bell" />
    </div>
@endsection
