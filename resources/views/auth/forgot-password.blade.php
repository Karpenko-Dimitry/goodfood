@extends('layouts.app')

@section('title', __('site.auth.forgot_title'))
@section('robots', 'noindex, follow')

@section('content')
    <x-auth-card :title="__('site.auth.forgot_title')" :text="__('site.auth.forgot_text')" :google="false">
        <form method="post" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div>
                <label class="label" for="email">{{ __('site.auth.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button class="btn-primary w-full">{{ __('site.auth.send_link') }}</button>
        </form>
        <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="text-brand hover:underline">← {{ __('site.auth.login') }}</a></p>
    </x-auth-card>
@endsection
