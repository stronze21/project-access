@extends('layouts.resident-portal', ['guest' => true])
@section('title', 'Activate Resident Account - SmartCity ACCESS')
@section('content')
<div class="auth-mpin-page">
    <div class="auth-mpin-brand">
        <div class="auth-mpin-logo-row"><img class="auth-mpin-seal" src="{{ asset('resident-portal/images/alaminos-seal.jpg') }}" alt="City seal"><img class="auth-mpin-access" src="{{ asset('resident-portal/images/access-logo.png') }}" alt="ACCESS"></div>
        <div class="auth-mpin-project">SmartCity ACCESS</div>
    </div>
    <section class="auth-mpin-panel identifier">
        <a class="text-button back-account" href="{{ route('resident-portal.login') }}"><span class="material-symbols-rounded">arrow_back</span> Back to sign in</a>
        <h1>Activate account</h1><p>Verify your registered resident information and create an MPIN.</p>
        <form method="POST" action="{{ route('resident-portal.register.store') }}" class="form-stack">
            @csrf
            <label>Resident ID<input name="resident_id" value="{{ old('resident_id') }}" required autocomplete="username"></label>
            <label>Last name<input name="last_name" value="{{ old('last_name') }}" required></label>
            <label>Birth date<input type="date" name="birth_date" value="{{ old('birth_date') }}" required></label>
            <label>Create 6-digit MPIN<input type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="mpin" required autocomplete="new-password"></label>
            <label>Confirm MPIN<input type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="mpin_confirmation" required autocomplete="new-password"></label>
            <button class="primary-button" type="submit">Activate account</button>
        </form>
    </section>
    <div class="auth-legal-links"><a href="{{ route('legal.privacy') }}">Privacy Notice</a><span>•</span><a href="{{ route('legal.terms') }}">Terms</a><span>•</span><a href="{{ route('legal.support') }}">Support</a></div>
</div>
@endsection
