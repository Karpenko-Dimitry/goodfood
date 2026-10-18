@extends('layouts.app')

@section('title', __('site.auth.login'))
@section('robots', 'noindex, follow')

@section('content')
    <x-auth-card :title="__('site.auth.login_title')" :text="__('site.auth.login_text')">
        <form method="post" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <div>
                <label class="label" for="email">{{ __('site.auth.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <div class="flex items-baseline justify-between">
                    <label class="label" for="password">{{ __('site.auth.password') }}</label>
                    <a href="{{ route('password.request') }}" class="text-xs text-brand hover:underline">{{ __('site.auth.forgot') }}</a>
                </div>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="field">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="accent-brand" @checked(old('remember'))>
                {{ __('site.auth.remember') }}
            </label>
            <button class="btn-primary w-full">{{ __('site.auth.login') }}</button>
        </form>
        <p class="mt-6 text-center text-sm text-muted">
            {{ __('site.auth.no_account') }} <a href="{{ route('register') }}" class="font-medium text-brand hover:underline">{{ __('site.auth.register') }}</a>
        </p>
    </x-auth-card>
@endsection
