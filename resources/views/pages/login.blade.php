@extends('layouts.app')

@section('title', 'Login - '.config('app.name', 'mailist DX'))

@section('content')
    <div class="mx-auto w-full max-w-md">
        <x-page-header title="Log in" subtitle="Sign in to mailist DX with your email or Google account." />

        <div class="space-y-4">
            <x-placeholder title="Email login code" description="Enter your email address and we will send you a one-time login code." icon="mail" height="h-40" />

            <div class="flex items-center gap-3">
                <div class="h-px flex-1 bg-border"></div>
                <span class="text-xs uppercase tracking-wide text-foreground-accent">or</span>
                <div class="h-px flex-1 bg-border"></div>
            </div>

            <x-placeholder title="Continue with Google" description="Sign in using your Google account." icon="chrome" height="h-24" />
        </div>
    </div>
@endsection
