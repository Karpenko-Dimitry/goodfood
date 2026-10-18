@extends('layouts.app')

@section('title', __('site.auth.reset_title'))
@section('robots', 'noindex, nofollow')

@section('content')
    <x-auth-card :title="__('site.auth.reset_title')" :google="false">
        <form method="post" action="{{ route('password.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="label" for="email">{{ __('site.auth.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="username" class="field">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">{{ __('site.auth.password') }}</label>
                <input id="password" name="password" type="password" required minlength="8" autofocus autocomplete="new-password" class="field">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">{{ __('site.auth.password_confirmation') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field">
            </div>
            <button class="btn-primary w-full">{{ __('site.auth.reset_password') }}</button>
        </form>
    </x-auth-card>
@endsection
