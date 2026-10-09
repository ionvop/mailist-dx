@extends('layouts.app')

@section('title', 'Login - '.config('app.name', 'mailist DX'))

@section('content')
    <div class="mx-auto w-full max-w-md">
        <x-page-header title="Log in" subtitle="Sign in to mailist DX with your email or Google account." />

        <div class="rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
            @if (session('status'))
                <x-alert class="mb-4">{{ session('status') }}</x-alert>
            @endif

            @if ($errors->any())
                <x-alert variant="error" class="mb-4">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form method="POST" action="{{ route('login.email') }}" class="space-y-4">
                @csrf

                <x-input
                    name="email"
                    label="Email address"
                    type="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    :autofocus="true"
                    :required="true"
                />

                <x-button type="submit">Send me a login code</x-button>
            </form>

            <div class="my-6 flex items-center gap-3">
                <div class="h-px flex-1 bg-border"></div>
                <span class="text-xs uppercase tracking-wide text-foreground-accent">or</span>
                <div class="h-px flex-1 bg-border"></div>
            </div>

            <x-button :href="route('auth.google.redirect')" variant="secondary">
                <x-lucide-chrome class="h-5 w-5" />
                Continue with Google
            </x-button>
        </div>
    </div>
@endsection
