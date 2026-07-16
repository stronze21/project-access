@php
    $photoUrl = $resident->photo_path ? Storage::url($resident->photo_path) : null;
    $signatureUrl = null;

    if ($resident->signature) {
        $signatureUrl = str_starts_with($resident->signature, 'data:') || str_starts_with($resident->signature, 'http')
            ? $resident->signature
            : asset(ltrim($resident->signature, '/'));
    }

    $givenName = trim($resident->first_name . ' ' . ($resident->suffix ?? ''));
    $birthdate = $resident->formattedBirthDate() ?? '';
@endphp

<section class="print-page card-front" data-resident-id="{{ $resident->resident_id }}" data-side="front">
    @if ($photoUrl)
        <img class="resident-photo editable-element" data-editor-key="photo" data-editor-label="Resident Photo"
            src="{{ $photoUrl }}" alt="Photo of {{ $resident->full_name }}">
    @else
        <span class="photo-placeholder editable-element" data-editor-key="photo" data-editor-label="Photo Placeholder">No photo</span>
    @endif

    <img class="front-artwork" src="{{ asset('images/id-cards/access-id-front.png') }}" alt="">

    <div data-editor-key="last-name" data-editor-label="Last Name" @class(['field-value', 'field-last-name', 'editable-element', 'compact' => mb_strlen($resident->last_name) > 15, 'very-compact' => mb_strlen($resident->last_name) > 22])>
        {{ $resident->last_name }}
    </div>
    <div data-editor-key="given-name" data-editor-label="Given Name" @class(['field-value', 'field-given-name', 'editable-element', 'compact' => mb_strlen($givenName) > 17, 'very-compact' => mb_strlen($givenName) > 24])>
        {{ $givenName }}
    </div>
    <div data-editor-key="middle-name" data-editor-label="Middle Name" @class(['field-value', 'field-middle-name', 'editable-element', 'compact' => mb_strlen($resident->middle_name ?? '') > 15, 'very-compact' => mb_strlen($resident->middle_name ?? '') > 22])>
        {{ $resident->middle_name }}
    </div>
    <div class="field-value field-birthdate editable-element" data-editor-key="birthdate" data-editor-label="Birthdate">{{ $birthdate }}</div>

    <span class="gender-label-mask" aria-hidden="true"></span>
    <span class="gender-label">Gender:</span>
    <div class="field-value field-gender editable-element" data-editor-key="gender" data-editor-label="Gender">{{ $resident->gender }}</div>
    <div class="field-value field-resident-id editable-element" data-editor-key="resident-id" data-editor-label="Resident ID">AC-{{ $resident->resident_id }}</div>

    @if ($signatureUrl)
        <img class="resident-signature editable-element" data-editor-key="signature" data-editor-label="Signature / Thumbmark"
            src="{{ $signatureUrl }}" alt="Signature or thumbmark of {{ $resident->full_name }}">
    @endif
</section>

<section class="print-page card-back" data-resident-id="{{ $resident->resident_id }}" data-side="back">
    <img class="back-artwork" src="{{ asset('images/id-cards/access-id-back.jpg') }}" alt="">
    <span class="back-qr-mask" aria-hidden="true"></span>
    <img class="resident-qr editable-element" data-editor-key="qr-code" data-editor-label="QR Code"
        src="{{ route('qrcode.resident', $resident->id) }}" alt="QR code for {{ $resident->full_name }}">
    <p class="sr-only">
        Property of City Government of Alaminos. This identification card is the property of LGU Alaminos City.
        It is for authorized use only. If found, please return this card to LGU Alaminos City or call
        (075) 551 2146. Unauthorized use is strictly prohibited.
    </p>
</section>
