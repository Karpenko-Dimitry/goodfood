@extends('layouts.app')

@section('title', __('site.nav.contact'))

@section('content')
    <x-page-hero :title="__('site.nav.contact')" :subtitle="__('site.contact.subtitle')" />

    <section class="section">
        <div class="container-x grid gap-8 md:grid-cols-3">
            <div class="card text-center">
                <span class="text-4xl">📍</span>
                <h3 class="card-title mt-4">{{ __('site.contact.address_title') }}</h3>
                <p class="text-muted">{{ __('site.contact.address') }}</p>
            </div>
            <div class="card text-center">
                <span class="text-4xl">📞</span>
                <h3 class="card-title mt-4">{{ __('site.contact.phone_title') }}</h3>
                <a href="tel:+1234567890" class="text-muted hover:text-brand">+ 123 456 7890</a>
            </div>
            <div class="card text-center">
                <span class="text-4xl">✉️</span>
                <h3 class="card-title mt-4">Email</h3>
                <a href="mailto:hello@dietcoach.test" class="text-muted hover:text-brand">hello@dietcoach.test</a>
            </div>
        </div>
    </section>
@endsection
