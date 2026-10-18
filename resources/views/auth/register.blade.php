@extends('layouts.app')

@section('title', __('site.auth.register'))
@section('robots', 'noindex, follow')

@section('content')
    <x-auth-card :title="__('site.auth.register_title')" :text="__('site.auth.login_text')">
        <form method="post" action="{{ route('register') }}" class="space-y-5">
            @csrf
            <div>
                <label class="label" for="name">{{ __('site.auth.name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="field">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="email">{{ __('site.auth.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="field">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">{{ __('site.auth.password') }}</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="field">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">{{ __('site.auth.password_confirmation') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field">
            </div>
            <button class="btn-primary w-full">{{ __('site.auth.register') }}</button>
        </form>
        <p class="mt-6 text-center text-sm text-muted">
            {{ __('site.auth.have_account') }} <a href="{{ route('login') }}" class="font-medium text-brand hover:underline">{{ __('site.auth.login') }}</a>
        </p>
    </x-auth-card>
@endsection
