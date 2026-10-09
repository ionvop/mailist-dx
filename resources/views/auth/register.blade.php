@extends('layouts.app')

@section('title', 'Create your account - '.config('app.name', 'mailist DX'))

@section('content')
    <div class="mx-auto w-full max-w-md">
        <x-page-header title="Almost there" subtitle="Pick a username to finish creating your account." />

        <div class="rounded-2xl border border-border bg-background-accent/40 p-6 sm:p-8">
            <p class="mb-4 text-sm text-foreground-accent">
                Your email <span class="font-medium text-foreground">{{ $email }}</span> is verified.
            </p>

            @if ($errors->any())
                <x-alert variant="error" class="mb-4">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                @csrf

                <x-input
                    name="username"
                    label="Username"
                    placeholder="sauce_hunter"
                    autocomplete="username"
                    minlength="3"
                    maxlength="30"
                    hint="Letters, numbers, hyphens, and underscores only."
                    :autofocus="true"
                    :required="true"
                />

                <x-button type="submit">Create account</x-button>
            </form>
        </div>
    </div>
@endsection
