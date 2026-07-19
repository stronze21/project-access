@extends('layouts.resident-portal', ['guest' => true])
@section('title', 'Resident Sign In - SmartCity ACCESS')
@section('content')
<div class="auth-mpin-page" data-mpin-login>
    <div class="pwa-login-loader" data-login-loader hidden role="status" aria-live="polite" aria-label="Authenticating your account">
        <div class="pwa-login-loader-card">
            <div class="pwa-login-loader-logos" aria-hidden="true">
                <img src="{{ asset('resident-portal/images/alaminos-seal.jpg') }}" alt="">
                <img src="{{ asset('resident-portal/images/access-logo.png') }}" alt="">
            </div>
            <h2>Signing you in</h2>
            <p>Authenticating your account...</p>
            <div class="pwa-login-progress" aria-hidden="true"><span></span></div>
            <div class="pwa-login-dots" aria-hidden="true"><span></span><span></span><span></span></div>
        </div>
    </div>
    <div class="auth-mpin-brand">
        <div class="auth-mpin-logo-row">
            <img class="auth-mpin-seal" src="{{ asset('resident-portal/images/alaminos-seal.jpg') }}" alt="City of Alaminos seal">
            <img class="auth-mpin-access" src="{{ asset('resident-portal/images/access-logo.png') }}" alt="Alaminos City ACCESS">
        </div>
        <div class="auth-mpin-project">SmartCity ACCESS</div>
    </div>
    <section class="auth-mpin-panel identifier" data-identifier-panel>
        <h1>Welcome back</h1><p>Sign in to your resident account</p>
        <label class="field-label" for="login">Resident ID, email, or phone</label>
        <div class="icon-field"><span class="material-symbols-rounded">person</span><input id="login" form="resident-login" name="login" value="{{ old('login') }}" required autocomplete="username" placeholder="R-2026-0001"></div>
        <button type="button" class="primary-button" data-mpin-continue>Continue</button>
        <a class="outline-button" href="{{ route('resident-portal.register') }}">Create New Account</a>
    </section>
    <section class="auth-mpin-panel unlock" data-mpin-panel hidden>
        <button type="button" class="text-button back-account" data-mpin-back><span class="material-symbols-rounded">arrow_back</span> Change account</button>
        <h1>Welcome back</h1><p>Enter your 6-digit MPIN to continue</p>
        <form id="resident-login" method="POST" action="{{ route('resident-portal.login.store') }}">
            @csrf
            <input type="hidden" name="mpin" data-mpin-value>
            <div class="mpin-label-row"><span>Enter your MPIN</span><button type="button" data-mpin-clear>Clear</button></div>
            <div class="mpin-entry" aria-live="polite">@for($i=0;$i<6;$i++)<span class="mpin-box" data-mpin-box></span>@endfor</div>
            <div class="auth-number-pad" aria-label="MPIN keypad">
                @foreach([1,2,3,4,5,6,7,8,9,null,0,'back'] as $key)
                    @if($key === null)<span></span>
                    @elseif($key === 'back')<button type="button" class="auth-number-key icon" data-mpin-delete aria-label="Delete digit"><span class="material-symbols-rounded">backspace</span></button>
                    @else<button type="button" class="auth-number-key" data-mpin-digit="{{ $key }}">{{ $key }}</button>@endif
                @endforeach
            </div>
            <button class="primary-button" type="submit" data-login-submit>Sign in</button>
        </form>
        <a class="text-button" href="{{ route('resident-portal.mpin.forgot') }}">Forgot MPIN?</a>
    </section>
    <div class="auth-legal-links"><a href="{{ route('legal.privacy') }}">Privacy Notice</a><span>•</span><a href="{{ route('legal.terms') }}">Terms</a><span>•</span><a href="{{ route('legal.support') }}">Support</a></div>
</div>
@endsection
