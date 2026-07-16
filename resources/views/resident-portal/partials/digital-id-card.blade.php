<button class="digital-id-card" type="button" data-flip-card aria-pressed="false" aria-label="Show the back of the resident ID card">
    <span class="digital-id-card-inner">
        <span class="digital-id-face digital-id-front">
            @if($photoUrl)<img class="portal-id-photo" data-resident-photo src="{{ $photoUrl }}" alt="{{ $resident->full_name }}">@else<span class="portal-id-photo placeholder material-symbols-rounded filled">person</span>@endif
            <img class="portal-id-front-artwork" src="{{ asset('resident-portal/images/id-cards/access-id-front.png') }}" alt="ACCESS resident ID front artwork">
            <span class="portal-id-value portal-id-last-name">{{ $resident->last_name }}</span>
            <span class="portal-id-value portal-id-given-name">{{ $givenName }}</span>
            <span class="portal-id-value portal-id-middle-name">{{ $resident->middle_name }}</span>
            <span class="portal-id-value portal-id-birthdate">{{ $resident->formattedBirthDate() }}</span>
            <span class="portal-id-gender-mask" aria-hidden="true"></span>
            <span class="portal-id-gender-label">Gender:</span>
            <span class="portal-id-value portal-id-gender">{{ $resident->gender }}</span>
            <span class="portal-id-value portal-id-resident-id">AC-{{ $resident->resident_id }}</span>
            @if($signatureUrl)<img class="portal-id-signature" src="{{ $signatureUrl }}" alt="Signature or thumbmark of {{ $resident->full_name }}">@endif
        </span>
        <span class="digital-id-face digital-id-back" aria-hidden="true">
            <img class="digital-id-back-artwork" src="{{ asset('resident-portal/images/id-cards/access-id-back.jpg') }}" alt="ACCESS resident ID back artwork">
            <span class="digital-id-back-qr-mask" aria-hidden="true"></span>
            <span class="digital-id-back-qr">{!! $qrSvg !!}</span>
        </span>
    </span>
</button>
<p class="digital-id-flip-hint" data-flip-hint><span class="material-symbols-rounded">touch_app</span> Tap the card to view its back</p>
