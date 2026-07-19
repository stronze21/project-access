@extends('layouts.resident-portal', ['guest' => true])
@section('title', 'Reset MPIN - SmartCity ACCESS')
@section('content')
<div class="auth-mpin-page">
    <div class="auth-mpin-brand">
        <div class="auth-mpin-logo-row"><img class="auth-mpin-seal" src="{{ asset('resident-portal/images/alaminos-seal.jpg') }}" alt="City of Alaminos seal"><img class="auth-mpin-access" src="{{ asset('resident-portal/images/access-logo.png') }}" alt="Alaminos City ACCESS"></div>
        <div class="auth-mpin-project">SmartCity ACCESS</div>
    </div>
    <section class="auth-mpin-panel identifier">
        <h1>Reset your MPIN</h1><p>Verify your resident record, then choose a new 6-digit MPIN.</p>
        <form class="form-stack" method="POST" action="{{ route('resident-portal.mpin.reset') }}">@csrf
            <label>Resident ID<input name="resident_id" value="{{ old('resident_id') }}" required autocomplete="username"></label>
            <label>Last name<input name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"></label>
            <label>Birth date<input type="date" name="birth_date" value="{{ old('birth_date') }}" required autocomplete="bday"></label>
            <label>New 6-digit MPIN<input type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="mpin" required autocomplete="new-password"></label>
            <label>Confirm new MPIN<input type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="mpin_confirmation" required autocomplete="new-password"></label>
            <button class="primary-button" type="submit">Reset MPIN</button>
        </form>
        <a class="outline-button" href="{{ route('resident-portal.login') }}">Back to sign in</a>
    </section>
</div>
@endsection
