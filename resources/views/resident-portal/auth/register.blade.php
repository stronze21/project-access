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
        <h1>Activate account</h1><p>Verify your resident information, email address, and create an MPIN.</p>
        <form method="POST" action="{{ route('resident-portal.register.store') }}" class="form-stack" data-activation-form>
            @csrf
            <label>Resident ID<input name="resident_id" value="{{ old('resident_id') }}" required autocomplete="username"></label>
            <label>Last name<input name="last_name" value="{{ old('last_name') }}" required></label>
            <label>Birth date<input type="date" name="birth_date" value="{{ old('birth_date') }}" required></label>
            <label>Email address<input type="email" name="email" value="{{ old('email', $emailChallengeAddress) }}" required autocomplete="email"></label>
            <button class="outline-button" type="submit" formaction="{{ route('resident-portal.register.email-code') }}" formnovalidate>
                {{ $emailChallengeId ? 'Send a new confirmation code' : 'Send confirmation code' }}
            </button>
            @if($emailChallengeId)
                <input type="hidden" name="email_challenge_id" value="{{ $emailChallengeId }}">
                <div class="portal-alert success"><span class="material-symbols-rounded filled">mark_email_read</span><span>Enter the six-digit code sent to {{ $emailChallengeAddress }}. It expires in 10 minutes.</span></div>
                <label>Confirmation code<input name="email_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code"></label>
            @else
                <input type="hidden" name="email_challenge_id" value="">
                <label>Confirmation code<input name="email_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required disabled placeholder="Send a code first"></label>
            @endif
            <label>Create 6-digit MPIN<input type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="mpin" required autocomplete="new-password"></label>
            <label>Confirm MPIN<input type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="mpin_confirmation" required autocomplete="new-password"></label>
            <div class="auth-consent">
                <input id="terms_accepted" type="checkbox" name="terms_accepted" value="1" data-legal-checkbox="terms" required disabled>
                <div class="auth-consent-copy"><label for="terms_accepted">I agree to the</label> <button type="button" class="auth-legal-button" data-legal-open="terms">Terms of Use</button><span>.</span></div>
            </div>
            <div class="auth-consent">
                <input id="privacy_notice_acknowledged" type="checkbox" name="privacy_notice_acknowledged" value="1" data-legal-checkbox="privacy" required disabled>
                <div class="auth-consent-copy"><label for="privacy_notice_acknowledged">I have read and acknowledge the</label> <button type="button" class="auth-legal-button" data-legal-open="privacy">Privacy Notice</button><span>.</span></div>
            </div>
            <div class="auth-consent">
                <input id="bhwis_import_consented" type="checkbox" name="bhwis_import_consented" value="1" data-legal-checkbox="consent" required disabled>
                <div class="auth-consent-copy"><label for="bhwis_import_consented">I consent to retrieving and importing my registered BHWIS information for account activation.</label> <button type="button" class="auth-legal-button" data-legal-open="consent">Read the consent details</button><span>.</span></div>
            </div>
            <button class="primary-button" type="submit" data-activation-submit disabled data-email-ready="{{ $emailChallengeId ? '1' : '0' }}">Verify email and activate account</button>
            <noscript><p class="auth-script-warning">JavaScript is required to review and accept the activation agreements.</p></noscript>
        </form>
    </section>
    <div class="auth-legal-links"><button type="button" data-legal-open="privacy">Privacy Notice</button><span>•</span><button type="button" data-legal-open="terms">Terms</button><span>•</span><a href="{{ route('legal.support') }}">Support</a></div>
</div>

@foreach([
    'terms' => ['title' => 'Terms of Use', 'url' => route('legal.terms')],
    'privacy' => ['title' => 'Privacy Notice', 'url' => route('legal.privacy')],
] as $legalKey => $legal)
    <dialog class="legal-modal" data-legal-dialog="{{ $legalKey }}" aria-labelledby="{{ $legalKey }}-modal-title">
        <div class="legal-modal-card">
            <header class="legal-modal-header">
                <div><span>Account activation</span><h2 id="{{ $legalKey }}-modal-title">{{ $legal['title'] }}</h2></div>
                <button type="button" class="legal-modal-close" data-legal-close aria-label="Close {{ $legal['title'] }}"><span class="material-symbols-rounded">close</span></button>
            </header>
            <iframe class="legal-modal-frame" data-legal-frame title="{{ $legal['title'] }}" data-src="{{ $legal['url'] }}"></iframe>
            <footer class="legal-modal-footer">
                <p data-legal-status>Scroll to the bottom to confirm you have read this document.</p>
                <button type="button" class="primary-button" data-legal-done disabled>Finish reading</button>
            </footer>
        </div>
    </dialog>
@endforeach

<dialog class="legal-modal" data-legal-dialog="consent" aria-labelledby="consent-modal-title">
    <div class="legal-modal-card">
        <header class="legal-modal-header">
            <div><span>Account activation</span><h2 id="consent-modal-title">BHWIS Information Import Consent</h2></div>
            <button type="button" class="legal-modal-close" data-legal-close aria-label="Close consent details"><span class="material-symbols-rounded">close</span></button>
        </header>
        <div class="legal-modal-copy" data-legal-scroll tabindex="0">
            <p>Project ACCESS first checks for a resident record already stored locally. If no local record exists under your Resident ID, the City Government of Alaminos will retrieve the corresponding record from the Barangay Health Worker Information System (BHWIS).</p>
            <h3>Information that may be imported</h3>
            <p>This may include your identifying and contact details, birth information, address, household information, and other personal or sensitive personal information already recorded in BHWIS and needed to verify your identity and set up your account.</p>
            <h3>How the information will be used</h3>
            <p>The information will be used to match your activation details with the official resident record, create and maintain your Project ACCESS profile, and make eligible city services available to you. It will be handled according to the Privacy Notice and applicable data-protection and government recordkeeping requirements.</p>
            <h3>Your choice</h3>
            <p>By checking the consent box, you authorize this retrieval and import for account activation. If you do not consent, the online activation cannot continue. You may contact the appropriate barangay or city office for assistance or another available verification process.</p>
            <h3>Pahintulot</h3>
            <p>Sa pag-check ng consent box, pinahihintulutan mo ang pagkuha at paglipat sa Project ACCESS ng iyong rehistradong impormasyon mula sa BHWIS para ma-verify ang iyong pagkakakilanlan at ma-activate ang account. Kung hindi ka pahihintulot, hindi maipagpapatuloy ang online activation at maaari kang humingi ng tulong sa kaukulang tanggapan ng barangay o lungsod.</p>
            <p class="legal-modal-end"><strong>End of consent details / Dulo ng detalye ng pahintulot</strong></p>
        </div>
        <footer class="legal-modal-footer">
            <p data-legal-status>Scroll to the bottom to confirm you have read these details.</p>
            <button type="button" class="primary-button" data-legal-done disabled>Finish reading</button>
        </footer>
    </div>
</dialog>
@endsection
