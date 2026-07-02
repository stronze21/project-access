@props([
    'user' => null,
    'size' => 'h-10 w-10',
    'textSize' => 'text-sm',
])

@php
    $photoUrl = $user?->profilePhotoUrl();
    $initials = $user?->profileInitials() ?? 'U';
@endphp

@if ($photoUrl)
    <img src="{{ $photoUrl }}"
         alt="{{ $user?->name ?? 'User' }} avatar"
         class="{{ $size }} rounded-full object-cover ring-2 ring-white/70 shadow-sm">
@else
    <span class="{{ $size }} {{ $textSize }} inline-flex items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 ring-2 ring-white/70 shadow-sm">
        {{ $initials }}
    </span>
@endif
