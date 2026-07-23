@extends('layouts.resident-portal')
@php
    $portal = fn (string $path = '') => url('/resident-portal'.($path ? '/'.ltrim($path, '/') : ''));
    $statusClass = fn (?string $status) => in_array($status, ['completed','released','distributed','resolved','closed','active','approved'], true) ? 'success' : (in_array($status, ['rejected','cancelled','urgent','critical','denied'], true) ? 'danger' : 'warning');
    $photoUrl = null;
    if ($resident->photo_path) {
        $photoPath = str_replace('\\', '/', $resident->photo_path);
        $photoUrl = str_starts_with($photoPath, 'http://') || str_starts_with($photoPath, 'https://') || str_starts_with($photoPath, 'data:')
            ? $photoPath
            : '/storage/'.ltrim(preg_replace('#^/?storage/#', '', $photoPath), '/');
    }
    $signatureUrl = null;
    if ($resident->signature) {
        $signaturePath = str_replace('\\', '/', $resident->signature);
        $signatureUrl = str_starts_with($signaturePath, 'http://') || str_starts_with($signaturePath, 'https://') || str_starts_with($signaturePath, 'data:')
            ? $signaturePath
            : '/storage/'.ltrim(preg_replace('#^/?storage/#', '', $signaturePath), '/');
    }
    $givenName = trim($resident->first_name.' '.($resident->suffix ?? ''));
    $flags = collect([
        'PWD' => $resident->is_pwd, 'Senior Citizen' => $resident->is_senior_citizen,
        'Solo Parent' => $resident->is_solo_parent, '4Ps Member' => $resident->is_4ps,
        'Scholar' => $resident->is_scholar, 'BHW' => $resident->is_bhw,
        'Indigenous' => $resident->is_indigenous, 'Registered Voter' => $resident->is_registered_voter,
    ])->filter()->keys();
@endphp
@section('title', 'SmartCity ACCESS Resident Portal')
@section('content')
<div class="app-content-padded page-transition">
@if(session('resident_portal_requires_mpin_update'))
    <div class="portal-alert warning"><span class="material-symbols-rounded filled">lock_reset</span><span>Please replace your temporary birthday MPIN in Settings.</span></div>
@endif

@if($screen === 'home')
    <div class="home-app-title"><span class="home-app-name-primary">SmartCity</span><span class="home-app-name-secondary">ACCESS</span></div>
    <div class="home-services-rail" aria-label="Resident services">
        @foreach([
            ['ayuda','volunteer_activism','AyudaHub','ayuda'],
            ['citizen-services/tracking','track_changes','Track','blue'],
            ['citizen-services/public-services','account_balance','Portals','green'],
            ['bosesmoto','record_voice_over','BosesMoTo','blue'],
            ['citizen-services/sos','emergency_share','SOS','red'],
            ['citizen-services/alerts','warning','Alerts','amber'],
        ] as [$href,$icon,$label,$tone])
            <a class="home-service-item" href="{{ $portal($href) }}"><span class="home-service-icon {{ $tone }}"><span class="material-symbols-rounded filled">{{ $icon }}</span></span><span>{{ $label }}</span></a>
        @endforeach
    </div>

    <p class="section-header">Your Digital ID</p>
    @include('resident-portal.partials.digital-id-card')

    @if($flags->isNotEmpty())
        <p class="section-header">Resident Information</p>
        <div class="native-card home-flags-strip"><span class="home-flags-icon"><span class="material-symbols-rounded filled">verified_user</span></span><div class="home-flags-badges">@foreach($flags as $flag)<span class="stat-badge blue">{{ $flag }}</span>@endforeach</div></div>
    @endif

    @if($resident->household)
        <p class="section-header">Household</p>
        <a class="native-card household-summary-link" href="{{ $portal('household') }}">
            <div class="info-row"><span class="info-row-icon green"><span class="material-symbols-rounded filled">location_on</span></span><div><small>Address</small><strong>{{ $resident->household->full_address }}</strong></div></div>
            <div class="home-household-stats">
                <div class="home-stat-item"><span class="home-stat-icon blue"><span class="material-symbols-rounded filled">groups</span></span><strong>{{ $resident->household->member_count ?? $resident->household->residents()->count() }}</strong><small>Members</small></div>
                <div class="home-stat-item"><span class="home-stat-icon purple"><span class="material-symbols-rounded filled">home</span></span><strong>{{ $resident->household->dwelling_type ?: 'N/A' }}</strong><small>Dwelling</small></div>
                <div class="home-stat-item"><span class="home-stat-icon green"><span class="material-symbols-rounded filled">bolt</span></span><strong>{{ $resident->household->has_electricity ? 'Yes' : 'No' }}</strong><small>Electric</small></div>
                <div class="home-stat-item"><span class="home-stat-icon blue"><span class="material-symbols-rounded filled">water_drop</span></span><strong>{{ $resident->household->has_water_supply ? 'Yes' : 'No' }}</strong><small>Water</small></div>
            </div>
            <span class="household-view-link">View household members <span class="material-symbols-rounded">chevron_right</span></span>
        </a>
    @endif

    @if($resident->emergency_contact_name)
        <p class="section-header">Emergency Contact</p>
        <div class="native-card info-row"><span class="info-row-icon red"><span class="material-symbols-rounded filled">emergency</span></span><div><small>{{ $resident->emergency_contact_relationship }}</small><strong>{{ $resident->emergency_contact_name }}</strong></div><a class="round-action red" href="tel:{{ $resident->emergency_contact_number }}" aria-label="Call emergency contact"><span class="material-symbols-rounded filled">call</span></a></div>
    @endif

    @if($data['upcoming']->isNotEmpty())
        <p class="section-header">Upcoming Assistance</p>
        @foreach($data['upcoming'] as $item)<a class="native-card list-card" href="{{ $portal('ayuda') }}"><span class="list-icon amber"><span class="material-symbols-rounded filled">event</span></span><div><strong>{{ $item->ayudaProgram?->name }}</strong><small>{{ optional($item->distribution_date)->format('M d, Y') ?: 'Schedule pending' }}</small></div><span class="status-chip warning">{{ ucfirst($item->status) }}</span></a>@endforeach
    @endif

@elseif($screen === 'digital-id')
    <div class="page-header"><div><span class="eyebrow">Official resident credential</span><h1>Digital ID</h1><p>Present this secure ID when accessing city services.</p></div></div>
    @include('resident-portal.partials.digital-id-card')
    <div class="native-card"><div class="info-row"><span class="info-row-icon blue"><span class="material-symbols-rounded filled">badge</span></span><div><small>Date issued</small><strong>{{ optional($resident->date_issue)->format('F d, Y') ?: 'Not available' }}</strong></div></div><div class="info-row"><span class="info-row-icon green"><span class="material-symbols-rounded filled">verified</span></span><div><small>Status</small><strong>Active resident</strong></div></div></div>

@elseif($screen === 'household')
    <a class="back-link" href="{{ $portal() }}"><span class="material-symbols-rounded">arrow_back</span> Home</a>
    <x-resident-portal-page-header icon="groups" title="My Household" subtitle="View the residents registered in your household." />
    <div class="native-card">
        <div class="info-row"><span class="info-row-icon green"><span class="material-symbols-rounded filled">location_on</span></span><div><small>Household address</small><strong>{{ $data['household']->full_address }}</strong></div></div>
        <div class="info-row"><span class="info-row-icon blue"><span class="material-symbols-rounded filled">tag</span></span><div><small>Household ID</small><strong>{{ $data['household']->household_id }}</strong></div></div>
    </div>
    <p class="section-header">Members ({{ $data['members']->count() }})</p>
    @forelse($data['members'] as $member)
        @php($memberPhoto = $member->photo_path ? '/storage/'.ltrim(preg_replace('#^/?storage/#', '', str_replace('\\', '/', $member->photo_path)), '/') : null)
        <div class="native-card household-member-card">
            @if($memberPhoto)<img src="{{ $memberPhoto }}" alt="">@else<span class="household-member-avatar material-symbols-rounded filled">person</span>@endif
            <div><strong>{{ $member->full_name }}</strong><small>{{ str($member->relationship_to_head ?: 'member')->replace('_', ' ')->headline() }} · Age {{ $member->getAge() }}</small><span>{{ $member->resident_id }}</span></div>
            @if($member->id === $resident->id)<span class="status-chip blue">You</span>@endif
        </div>
    @empty
        <x-resident-portal-empty icon="group_off" title="No household members" message="No active residents are currently registered in this household." />
    @endforelse

@elseif($screen === 'ayuda')
    <x-resident-portal-page-header icon="volunteer_activism" title="AyudaHub" subtitle="Your city assistance and distribution history." />
    <div class="summary-grid"><div class="summary-card"><span class="material-symbols-rounded filled">inventory_2</span><strong>{{ $data['received'] }}</strong><small>Received</small></div><div class="summary-card"><span class="material-symbols-rounded filled">payments</span><strong>₱{{ number_format($data['total'], 2) }}</strong><small>Total value</small></div></div>
    <p class="section-header">Assistance history</p>
    @forelse($data['items'] as $item)
        <div class="native-card list-card"><span class="list-icon amber"><span class="material-symbols-rounded filled">redeem</span></span><div><strong>{{ $item->ayudaProgram?->name ?? 'City Assistance' }}</strong><small>{{ optional($item->distribution_date)->format('M d, Y') ?: 'Schedule pending' }} · {{ $item->batch?->location }}</small><b>₱{{ number_format($item->amount ?? 0, 2) }}</b></div><span class="status-chip {{ $statusClass($item->status) }}">{{ ucfirst($item->status) }}</span></div>
    @empty<x-resident-portal-empty icon="volunteer_activism" title="No assistance records" message="Your eligible and received programs will appear here." />@endforelse
    {{ $data['items']->links() }}

@elseif(str_starts_with($screen, 'announcements/'))
    <a class="back-link" href="{{ $portal('announcements') }}"><span class="material-symbols-rounded">arrow_back</span> Updates</a>
    <article class="native-card detail-card"><span class="announcement-type-icon {{ $data['item']->type }}"><span class="material-symbols-rounded filled">campaign</span></span><div class="chip-row"><span class="status-chip blue">{{ ucfirst($data['item']->type) }}</span>@if($data['item']->is_pinned)<span class="status-chip warning">Pinned</span>@endif </div><h1>{{ $data['item']->title }}</h1><small>{{ optional($data['item']->published_at)->format('F d, Y · g:i A') }}</small>@if($data['item']->image_path)<img class="announcement-image" src="{{ Storage::disk('public')->url($data['item']->image_path) }}" alt="">@endif <div class="rich-copy">{!! nl2br(e($data['item']->content)) !!}</div></article>

@elseif($screen === 'announcements')
    <div class="page-header split"><div><span class="eyebrow">City updates</span><h1>Announcements</h1><p>Official news and resident notifications.</p></div><span class="header-icon blue material-symbols-rounded filled">notifications</span></div>
    <div class="filter-chips"><a class="active" href="{{ $portal('announcements') }}">All</a><a href="?type=emergency">Emergency</a><a href="?type=program">Programs</a><a href="#notifications">Notifications ({{ $data['unread'] }})</a></div>
    @forelse($data['items'] as $item)
        <a class="native-card announcement-card {{ $item->is_pinned ? 'pinned' : '' }}" href="{{ $portal('announcements/'.$item->id) }}"><span class="announcement-type-icon {{ $item->type }}"><span class="material-symbols-rounded filled">{{ $item->type === 'emergency' ? 'warning' : 'campaign' }}</span></span><div><div class="chip-row"><span class="status-chip blue">{{ ucfirst($item->type) }}</span>@if($item->is_pinned)<span class="status-chip warning">Pinned</span>@endif</div><strong>{{ $item->title }}</strong><p>{{ Str::limit($item->content, 120) }}</p><small>{{ optional($item->published_at)->diffForHumans() }}</small></div><span class="material-symbols-rounded">chevron_right</span></a>
    @empty<x-resident-portal-empty icon="notifications_off" title="No updates yet" message="New city announcements will appear here." />@endforelse
    {{ $data['items']->links() }}
    <div id="notifications" class="section-heading-row"><p class="section-header">Your notifications</p>@if($data['unread'])<form method="POST" action="{{ route('resident-portal.notifications.read-all') }}">@csrf<button class="text-button" type="submit">Mark all read</button></form>@endif </div>
    @forelse($data['notifications'] as $notification)<form method="POST" action="{{ route('resident-portal.notifications.read',$notification) }}">@csrf<button class="native-card notification-card {{ $notification->read_at ? '' : 'unread' }}" type="submit"><span class="list-icon blue"><span class="material-symbols-rounded filled">notifications</span></span><span><strong>{{ $notification->title }}</strong><small>{{ $notification->body }}</small><em>{{ $notification->created_at->diffForHumans() }}</em></span>@unless($notification->read_at)<i></i>@endunless </button></form>@empty<x-resident-portal-empty icon="notifications_none" title="No personal notifications" message="Your resident notifications will appear here." />@endforelse

@elseif($screen === 'profile')
    <div class="profile-header">
        <div class="profile-avatar-container">@if($photoUrl)<img class="profile-avatar-img" src="{{ $photoUrl }}" alt="">@else<span class="profile-avatar-placeholder material-symbols-rounded filled">person</span>@endif<span class="profile-avatar-badge material-symbols-rounded filled">verified</span></div>
        <h1>{{ $resident->full_name }}</h1><span>{{ $resident->resident_id }}</span><div class="profile-tags">@foreach($flags as $flag)<span>{{ $flag }}</span>@endforeach</div>
    </div>
    <div class="profile-actions-row"><a href="{{ $portal('digital-id') }}"><span class="material-symbols-rounded filled">badge</span><small>Digital ID</small></a><a href="{{ $portal('settings') }}"><span class="material-symbols-rounded filled">settings</span><small>Settings</small></a><a href="tel:{{ $resident->emergency_contact_number }}"><span class="material-symbols-rounded filled">call</span><small>Emergency</small></a></div>
    <p class="section-header">Personal information</p>
    <div class="native-card"><div class="info-row"><span class="info-row-icon blue"><span class="material-symbols-rounded filled">cake</span></span><div><small>Birth date</small><strong>{{ optional($resident->birth_date)->format('F d, Y') }} · Age {{ $resident->getAge() }}</strong></div></div><div class="info-row"><span class="info-row-icon green"><span class="material-symbols-rounded filled">phone</span></span><div><small>Contact</small><strong>{{ $resident->contact_number ?: 'Not provided' }}</strong></div></div><div class="info-row"><span class="info-row-icon purple"><span class="material-symbols-rounded filled">mail</span></span><div><small>Email</small><strong>{{ $resident->email ?: 'Not provided' }}</strong></div></div></div>
    <a class="native-card action-row" href="{{ $portal('settings') }}"><span class="material-symbols-rounded filled">manage_accounts</span><div><strong>Edit profile and security</strong><small>Contact details, photo, emergency contact, MPIN</small></div><span class="material-symbols-rounded">chevron_right</span></a>

@elseif($screen === 'settings')
    <a class="back-link" href="{{ $portal('profile') }}"><span class="material-symbols-rounded">arrow_back</span> Profile</a>
    <x-resident-portal-page-header icon="settings" title="Settings" subtitle="Manage your resident account." />
    <p class="section-header">Identity media replacement</p>
    <div class="portal-alert warning"><span class="material-symbols-rounded filled">verified_user</span><span>Profile photos and signatures are identity records. Replacements take effect only after staff verification and approval.</span></div>
    <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.profile.photo') }}" enctype="multipart/form-data">@csrf<label>Proposed profile photo<input type="file" name="photo" accept="image/jpeg,image/png" capture="user" required></label><label>Reason for replacement<textarea name="reason" rows="3" minlength="10" maxlength="1000" required>{{ old('reason') }}</textarea></label><button class="primary-button" type="submit">Submit photo request</button></form>
    <p class="section-header">Contact information</p>
    <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.profile.update') }}">@csrf @method('PUT')<label>Contact number<input name="contact_number" value="{{ old('contact_number',$resident->contact_number) }}"></label><label>Email<input type="email" name="email" value="{{ old('email',$resident->email) }}"></label><label>Occupation<input name="occupation" value="{{ old('occupation',$resident->occupation) }}"></label><label>Emergency contact name<input name="emergency_contact_name" value="{{ old('emergency_contact_name',$resident->emergency_contact_name) }}"></label><label>Relationship<input name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship',$resident->emergency_contact_relationship) }}"></label><label>Emergency contact number<input name="emergency_contact_number" value="{{ old('emergency_contact_number',$resident->emergency_contact_number) }}"></label><button class="primary-button" type="submit">Save changes</button></form>
    <p class="section-header">Security</p>
    <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.mpin.update') }}">@csrf @method('PUT')<label>Current MPIN<input type="password" inputmode="numeric" maxlength="6" name="current_mpin" required></label><label>New MPIN<input type="password" inputmode="numeric" maxlength="6" name="mpin" required></label><label>Confirm new MPIN<input type="password" inputmode="numeric" maxlength="6" name="mpin_confirmation" required></label><button class="primary-button" type="submit">Change MPIN</button></form>
    <p class="section-header">Signature replacement</p><form class="native-card form-stack" method="POST" action="{{ route('resident-portal.profile.signature') }}" data-signature-form>@csrf<canvas class="signature-pad" width="400" height="170" data-signature-pad aria-label="Draw your proposed signature"></canvas><input type="hidden" name="signature" data-signature-value><label>Reason for replacement<textarea name="reason" rows="3" minlength="10" maxlength="1000" required></textarea></label><div class="signature-actions"><button class="outline-button" type="button" data-signature-clear>Clear</button><button class="primary-button" type="submit">Submit signature request</button></div></form>
    <p class="section-header">Replacement request history</p>
    @forelse($data['identityRequests'] as $item)<div class="native-card list-card"><span class="list-icon blue"><span class="material-symbols-rounded filled">{{ $item->type === 'photo' ? 'photo_camera' : 'draw' }}</span></span><div><strong>{{ str($item->type)->headline() }} replacement</strong><small>{{ $item->reference_number }} · {{ $item->created_at->format('M d, Y') }}@if($item->status === 'denied' && $item->review_reason) · {{ $item->review_reason }}@endif</small></div><span class="status-chip {{ $statusClass($item->status) }}">{{ str($item->status)->headline() }}</span></div>@empty<x-resident-portal-empty icon="verified_user" title="No replacement requests" message="Your photo and signature replacement requests will appear here." />@endforelse
    <div class="native-card action-list"><a href="{{ $portal('support') }}"><span class="material-symbols-rounded filled">support_agent</span> Support <span class="material-symbols-rounded">chevron_right</span></a><a href="{{ $portal('account-deletion') }}"><span class="material-symbols-rounded filled">delete_forever</span> Request account deletion <span class="material-symbols-rounded">chevron_right</span></a></div>
    <form method="POST" action="{{ route('resident-portal.logout') }}">@csrf<button class="danger-button" type="submit"><span class="material-symbols-rounded">logout</span> Sign out</button></form>

@elseif($screen === 'support')
    <a class="back-link" href="{{ $portal('settings') }}"><span class="material-symbols-rounded">arrow_back</span> Settings</a><x-resident-portal-page-header icon="support_agent" title="Resident Support" subtitle="Contact the ACCESS support team." />
    <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.support.store') }}">@csrf<label>Category<select name="category"><option value="account">Account</option><option value="technical">Technical</option><option value="service-request">Service request</option><option value="privacy">Privacy</option><option value="emergency">Emergency</option><option value="other">Other</option></select></label><label>Subject<input name="subject" required></label><label>Message<textarea name="message" rows="6" maxlength="4000" required></textarea></label><button class="primary-button" type="submit">Send support request</button></form><p class="section-header">Request history</p>@forelse($data['items'] as $item)<div class="native-card list-card"><span class="list-icon blue"><span class="material-symbols-rounded filled">support_agent</span></span><div><strong>{{ $item->subject }}</strong><small>{{ $item->reference_number }} · {{ $item->submitted_at?->format('M d, Y') }}</small></div><span class="status-chip {{ $statusClass($item->status) }}">{{ str($item->status)->headline() }}</span></div>@empty<x-resident-portal-empty icon="support_agent" title="No support requests" message="Your support history will appear here." />@endforelse{{ $data['items']->links() }}

@elseif($screen === 'account-deletion')
    <a class="back-link" href="{{ $portal('settings') }}"><span class="material-symbols-rounded">arrow_back</span> Settings</a><x-resident-portal-page-header icon="delete_forever" title="Data Request" subtitle="Request deletion of your resident portal account data." tone="red" />
    <div class="portal-alert warning"><span class="material-symbols-rounded filled">info</span><span>City records required by law may be retained even after portal account deletion.</span></div><form class="native-card form-stack" method="POST" action="{{ route('resident-portal.account-deletion.store') }}">@csrf<label>Requested action<select name="requested_action"><option value="delete-account-and-data">Delete portal account and eligible data</option><option value="delete-app-data-only">Delete app-specific data only</option></select></label><label>Reason (optional)<textarea name="reason" rows="5" maxlength="2000"></textarea></label><label class="checkbox-label"><input type="checkbox" name="retention_acknowledged" value="1" required> I understand some government records may need to be retained.</label><button class="danger-button" type="submit">Submit deletion request</button></form><p class="section-header">Request history</p>@forelse($data['items'] as $item)<div class="native-card list-card"><span class="list-icon red"><span class="material-symbols-rounded filled">delete_forever</span></span><div><strong>{{ str($item->requested_action)->headline() }}</strong><small>{{ $item->reference_number }} · {{ $item->submitted_at?->format('M d, Y') }}</small></div><span class="status-chip {{ $statusClass($item->status) }}">{{ str($item->status)->headline() }}</span></div>@empty<x-resident-portal-empty icon="delete_forever" title="No deletion requests" message="Any data requests you submit will appear here." />@endforelse{{ $data['items']->links() }}

@elseif($screen === 'citizen-services')
    <div class="cs-hero"><span class="cs-kicker">Alaminos City Citizens' E-Services Solutions</span><img src="{{ asset('resident-portal/images/access-logo.png') }}" alt="ACCESS"><div class="cs-definition"><span class="material-symbols-rounded filled">wifi_tethering</span> ACCESS brings city services closer, faster, and safer through your resident portal.</div><span class="eyebrow">Resident portal</span><h1>Citizen Services</h1><p>Track services, open official portals, access programs, and reach emergency support.</p></div>
    <div class="cs-benefit-row"><span><i class="material-symbols-rounded filled">touch_app</i>Convenient</span><span><i class="material-symbols-rounded filled">verified_user</i>Secure</span><span><i class="material-symbols-rounded filled">speed</i>Efficient</span></div>
    <div class="cs-tile-grid">
        @foreach([
            ['bosesmoto','record_voice_over','BosesMoTo','Complaints, polls, community voice','blue'],
            ['citizen-services/tracking','track_changes','Track Services','Follow submitted requests','blue'],
            ['citizen-services/public-services','account_balance','City Portals','Open public services','green'],
            ['ayuda','volunteer_activism','AyudaHub','Programs and assistance','amber'],
            ['citizen-services/sos','emergency_share','Emergency SOS','Request urgent help','red'],
        ] as [$href,$icon,$title,$sub,$tone])
            <a class="cs-tile {{ $tone }}" href="{{ $portal($href) }}"><span class="cs-tile-icon material-symbols-rounded filled">{{ $icon }}</span><strong>{{ $title }}</strong><small>{{ $sub }}</small><span class="material-symbols-rounded">arrow_forward</span></a>
        @endforeach
    </div>
    <p class="section-header">ACCESS Services</p><div class="native-card action-list"><a href="{{ $portal('citizen-services/alerts') }}"><span class="material-symbols-rounded filled red-text">warning</span> Emergency alerts <span class="material-symbols-rounded">chevron_right</span></a><a href="{{ $portal('announcements') }}"><span class="material-symbols-rounded filled blue-text">campaign</span> Updates and notifications <span class="material-symbols-rounded">chevron_right</span></a><a href="{{ $portal('citizen-services/grievances') }}"><span class="material-symbols-rounded filled amber-text">report_problem</span> Report an issue <span class="material-symbols-rounded">chevron_right</span></a></div>

@elseif($screen === 'citizen-services/tracking')
    <a class="back-link" href="{{ $portal('citizen-services') }}"><span class="material-symbols-rounded">arrow_back</span> Services</a><x-resident-portal-page-header icon="track_changes" title="Track Services" subtitle="Follow every resident request in one place." />
    <a class="primary-button" href="{{ $portal('citizen-services/tracking/new') }}"><span class="material-symbols-rounded">add</span> New service request</a>
    <p class="section-header">Your requests</p>@forelse($data['items'] as $item)<a class="native-card list-card" href="{{ $portal('citizen-services/tracking/'.$item->id) }}"><span class="list-icon blue"><span class="material-symbols-rounded filled">description</span></span><div><strong>{{ $item->service_name }}</strong><small>{{ $item->reference_number }} · {{ $item->current_step }}</small></div><span class="status-chip {{ $statusClass($item->status) }}">{{ str($item->status)->headline() }}</span></a>@empty<x-resident-portal-empty icon="inbox" title="No service requests" message="Start a request and track its progress here." />@endforelse{{ $data['items']->links() }}

@elseif($screen === 'citizen-services/tracking/new')
    <a class="back-link" href="{{ $portal('citizen-services/tracking') }}"><span class="material-symbols-rounded">arrow_back</span> Tracking</a><x-resident-portal-page-header icon="add_task" title="New Service Request" subtitle="Tell the city service you need." />
    <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.services.store') }}">@csrf<label>Service type<select name="service_type" required>@foreach($data['serviceTypes'] as $type)<option value="{{ $type->code }}" @selected(old('service_type') === $type->code)>{{ $type->name }}</option>@endforeach</select></label><label>Service name<input name="service_name" value="{{ old('service_name') }}" required placeholder="e.g. Barangay clearance"></label><label>Notes<textarea name="notes" rows="5" placeholder="Add useful details">{{ old('notes') }}</textarea></label><button class="primary-button" type="submit">Submit request</button></form>

@elseif(preg_match('#citizen-services/tracking/\d+#',$screen))
    <a class="back-link" href="{{ $portal('citizen-services/tracking') }}"><span class="material-symbols-rounded">arrow_back</span> Tracking</a><article class="native-card detail-card"><span class="header-icon blue material-symbols-rounded filled">description</span><span class="status-chip {{ $statusClass($data['item']->status) }}">{{ str($data['item']->status)->headline() }}</span><h1>{{ $data['item']->service_name }}</h1><p>{{ $data['item']->reference_number }}</p><div class="timeline"><div class="active"><b>Submitted</b><small>{{ optional($data['item']->submitted_at)->format('M d, Y g:i A') }}</small></div><div class="active"><b>{{ $data['item']->current_step }}</b><small>Latest update</small></div>@if($data['item']->completed_at)<div class="active"><b>Completed</b><small>{{ $data['item']->completed_at->format('M d, Y') }}</small></div>@endif</div>@if($data['item']->notes)<div class="rich-copy">{{ $data['item']->notes }}</div>@endif</article>

@elseif($screen === 'citizen-services/public-services')
    <a class="back-link" href="{{ $portal('citizen-services') }}"><span class="material-symbols-rounded">arrow_back</span> Services</a><x-resident-portal-page-header icon="account_balance" title="City Portals" subtitle="Open official public services." />
    @forelse($data['items'] as $type => $links)<p class="section-header">{{ str($type)->headline() }}</p><div class="native-card action-list">@foreach($links as $link)<a href="{{ $link->url }}" target="_blank" rel="noopener"><span class="portal-service-icon material-symbols-rounded filled">{{ $link->material_icon }}</span><span><strong>{{ $link->title }}</strong><small>{{ $link->description }}</small></span><span class="material-symbols-rounded">open_in_new</span></a>@endforeach</div>@empty<x-resident-portal-empty icon="public_off" title="No portals available" message="Official service links will appear here." />@endforelse

@elseif($screen === 'citizen-services/grievances')
    <a class="back-link" href="{{ $portal('citizen-services') }}"><span class="material-symbols-rounded">arrow_back</span> Services</a><x-resident-portal-page-header icon="report_problem" title="Report an Issue" subtitle="Send a grievance and track the response." />
    <details class="native-card disclosure" {{ $errors->any() ? 'open' : '' }}><summary><span class="material-symbols-rounded filled">add_location_alt</span> Submit a new report</summary><form class="form-stack" method="POST" action="{{ route('resident-portal.grievances.store') }}" enctype="multipart/form-data">@csrf<label>Category<select name="category" required><option>Roads and infrastructure</option><option>Waste and sanitation</option><option>Public safety</option><option>Health services</option><option>Other</option></select></label><label>Subject<input name="subject" required></label><label>Description<textarea name="description" rows="5" required></textarea></label><label>Location<input name="location_label" data-location-label></label><div class="location-fields"><input type="hidden" name="latitude" data-latitude><input type="hidden" name="longitude" data-longitude><button type="button" class="outline-button" data-get-location><span class="material-symbols-rounded">my_location</span> Use my location</button></div><label>Photo (optional)<input type="file" name="photo" accept="image/*" capture="environment"></label><button class="primary-button" type="submit">Submit report</button></form></details>
    <p class="section-header">Report history</p>@forelse($data['items'] as $item)<a class="native-card list-card" href="{{ $portal('citizen-services/grievances/'.$item->id) }}"><span class="list-icon amber"><span class="material-symbols-rounded filled">report</span></span><div><strong>{{ $item->subject }}</strong><small>{{ $item->reference_number }} · {{ $item->category }}</small></div><span class="status-chip {{ $statusClass($item->status) }}">{{ str($item->status)->headline() }}</span></a>@empty<x-resident-portal-empty icon="task_alt" title="No reports" message="You have not submitted a grievance." />@endforelse{{ $data['items']->links() }}

@elseif(preg_match('#citizen-services/grievances/\d+#',$screen))
    <a class="back-link" href="{{ $portal('citizen-services/grievances') }}"><span class="material-symbols-rounded">arrow_back</span> Reports</a><article class="native-card detail-card"><span class="status-chip {{ $statusClass($data['item']->status) }}">{{ str($data['item']->status)->headline() }}</span><h1>{{ $data['item']->subject }}</h1><small>{{ $data['item']->reference_number }} · {{ $data['item']->category }}</small><div class="rich-copy">{{ $data['item']->description }}</div>@if($data['item']->location_label)<p><span class="material-symbols-rounded">location_on</span> {{ $data['item']->location_label }}</p>@endif @if($data['item']->admin_response)<div class="response-box"><strong>City response</strong><p>{{ $data['item']->admin_response }}</p></div>@endif </article>

@elseif($screen === 'citizen-services/alerts')
    <a class="back-link" href="{{ $portal('citizen-services') }}"><span class="material-symbols-rounded">arrow_back</span> Services</a><x-resident-portal-page-header icon="warning" title="Emergency Alerts" subtitle="Current city safety advisories." tone="red" />
    @forelse($data['items'] as $item)<a class="native-card alert-card {{ $item->severity }}" href="{{ $portal('citizen-services/alerts/'.$item->id) }}"><span class="list-icon red"><span class="material-symbols-rounded filled">warning</span></span><div><span class="status-chip danger">{{ str($item->severity)->headline() }}</span><strong>{{ $item->title }}</strong><p>{{ Str::limit($item->message,120) }}</p><small>{{ optional($item->starts_at)->diffForHumans() }}</small></div></a>@empty<x-resident-portal-empty icon="verified_user" title="No active alerts" message="There are no emergency advisories right now." />@endforelse{{ $data['items']->links() }}

@elseif(preg_match('#citizen-services/alerts/\d+#',$screen))
    <a class="back-link" href="{{ $portal('citizen-services/alerts') }}"><span class="material-symbols-rounded">arrow_back</span> Alerts</a><article class="native-card detail-card emergency"><span class="header-icon red material-symbols-rounded filled">warning</span><span class="status-chip danger">{{ str($data['item']->severity)->headline() }}</span><h1>{{ $data['item']->title }}</h1><small>{{ optional($data['item']->starts_at)->format('F d, Y g:i A') }}</small><div class="rich-copy">{!! nl2br(e($data['item']->message)) !!}</div></article>

@elseif($screen === 'citizen-services/sos')
    <a class="back-link" href="{{ $portal('citizen-services') }}"><span class="material-symbols-rounded">arrow_back</span> Services</a><div class="sos-hero"><span class="material-symbols-rounded filled">emergency_share</span><h1>Emergency SOS</h1><p>Send your location to the city emergency team. Use only when immediate help is needed.</p></div>
    <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.sos.store') }}">@csrf<label>Emergency department<select name="sos_department_id"><option value="">Nearest available team</option>@foreach($data['departments'] as $department)<option value="{{ $department->id }}">{{ $department->name }}{{ $department->hotline ? ' · '.$department->hotline : '' }}</option>@endforeach</select></label><label>Contact number<input name="contact_number" value="{{ $resident->contact_number }}"></label><label>What is happening?<textarea name="message" rows="4"></textarea></label><label>Location<input name="location_label" data-location-label placeholder="Location description"></label><input type="hidden" name="latitude" data-latitude><input type="hidden" name="longitude" data-longitude><button type="button" class="outline-button" data-get-location><span class="material-symbols-rounded">my_location</span> Get current location</button><button class="sos-button" type="submit"><span class="material-symbols-rounded filled">sos</span> SEND EMERGENCY SOS</button></form><a class="outline-button" href="{{ $portal('citizen-services/sos/history') }}">View SOS history</a>

@elseif($screen === 'citizen-services/sos/history')
    <a class="back-link" href="{{ $portal('citizen-services/sos') }}"><span class="material-symbols-rounded">arrow_back</span> SOS</a><x-resident-portal-page-header icon="history" title="SOS History" subtitle="Your previous emergency requests." tone="red" />@forelse($data['items'] as $item)<div class="native-card list-card"><span class="list-icon red"><span class="material-symbols-rounded filled">emergency</span></span><div><strong>{{ $item->department?->name ?? 'Emergency response' }}</strong><small>{{ $item->reference_number }} · {{ $item->created_at->format('M d, Y g:i A') }}</small><p>{{ $item->message }}</p></div><span class="status-chip {{ $statusClass($item->status) }}">{{ str($item->status)->headline() }}</span></div>@empty<x-resident-portal-empty icon="health_and_safety" title="No SOS history" message="Your emergency requests will appear here." />@endforelse{{ $data['items']->links() }}

@elseif($screen === 'bosesmoto')
    <a class="back-link" href="{{ $portal('citizen-services') }}"><span class="material-symbols-rounded">arrow_back</span> Services</a><div class="bmt-hero"><img src="{{ asset('resident-portal/images/bosesmoto-logo.jpg') }}" alt="BosesMoTo"><div><span class="eyebrow">Your voice matters</span><h1>BosesMoTo</h1><p>Join public conversations and help improve Alaminos City.</p></div></div>
    <div class="cs-tile-grid">@foreach([['bosesmoto/my-complaints','assignment','My Complaints','Submit and track your complaints','green'],['bosesmoto/polls','poll','Polls','Vote on city questions','purple'],['bosesmoto/community','groups','Community Voice','Share resident sentiments','red']] as [$href,$icon,$title,$sub,$tone])<a class="cs-tile {{ $tone }}" href="{{ $portal($href) }}"><span class="cs-tile-icon material-symbols-rounded filled">{{ $icon }}</span><strong>{{ $title }}</strong><small>{{ $sub }}</small><span class="material-symbols-rounded">arrow_forward</span></a>@endforeach</div>

@elseif($screen === 'bosesmoto/my-complaints')
    <a class="back-link" href="{{ $portal('bosesmoto') }}"><span class="material-symbols-rounded">arrow_back</span> BosesMoTo</a><x-resident-portal-page-header icon="forum" title="My Complaints" subtitle="Submit concerns and track their progress." />
    <a class="primary-button" href="{{ $portal('bosesmoto/submit') }}"><span class="material-symbols-rounded">add</span> Submit Complaint</a>
    <p class="section-header">Your complaints</p>@forelse($data['items'] as $item)<a class="native-card complaint-card" href="{{ $portal('bosesmoto/my-complaints/'.$item->id) }}"><div class="chip-row"><span class="status-chip {{ $statusClass($item->status) }}">{{ str($item->status)->headline() }}</span><span class="status-chip blue">{{ $item->category?->name }}</span></div><strong>{{ $item->title }}</strong><p>{{ $item->short_summary }}</p><small><span class="material-symbols-rounded filled">location_on</span>{{ $item->barangay?->name ?? 'Alaminos City' }} · {{ $item->reference_code }}</small></a>@empty<x-resident-portal-empty icon="forum" title="No complaints yet" message="Submit a complaint to begin tracking it here." />@endforelse{{ $data['items']->links() }}

@elseif(preg_match('#^bosesmoto/my-complaints/\d+/edit$#',$screen))
    <a class="back-link" href="{{ $portal('bosesmoto/my-complaints/'.$data['item']->id) }}"><span class="material-symbols-rounded">arrow_back</span> Complaint</a><x-resident-portal-page-header icon="edit" title="Edit Complaint" subtitle="Update your report before city processing begins." />
    <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.complaints.update',$data['item']) }}">@csrf @method('PUT')<label>Title<input name="title" value="{{ $data['item']->title }}" required></label><label>Short summary<textarea name="short_summary" rows="2" required>{{ $data['item']->short_summary }}</textarea></label><label>Description<textarea name="description" rows="6" required>{{ $data['item']->description }}</textarea></label><label>Category<select name="category_id">@foreach($data['categories'] as $category)<option value="{{ $category->id }}" @selected($category->id === $data['item']->category_id)>{{ $category->name }}</option>@endforeach</select></label><label>Barangay<select name="barangay_id"><option value="">Select barangay</option>@foreach($data['barangays'] as $barangay)<option value="{{ $barangay->id }}" @selected($barangay->id === $data['item']->barangay_id)>{{ $barangay->name }}</option>@endforeach</select></label><button class="primary-button" type="submit">Save complaint</button></form>

@elseif(preg_match('#^bosesmoto/my-complaints/\d+$#',$screen))
    <a class="back-link" href="{{ $portal('bosesmoto/my-complaints') }}"><span class="material-symbols-rounded">arrow_back</span> My Complaints</a><article class="native-card detail-card"><div class="chip-row"><span class="status-chip {{ $statusClass($data['item']->status) }}">{{ str($data['item']->status)->headline() }}</span><span class="status-chip blue">{{ $data['item']->category?->name }}</span></div><h1>{{ $data['item']->title }}</h1><p>{{ $data['item']->short_summary }}</p><small>{{ $data['item']->reference_code }} · {{ $data['item']->created_at->format('M d, Y') }}</small><div class="rich-copy">{!! nl2br(e($data['item']->description)) !!}</div>@if($data['item']->resolution_summary)<div class="response-box"><strong>Resolution</strong><p>{{ $data['item']->resolution_summary }}</p></div>@endif <div class="inline-actions">@if($data['item']->canBeEditedByCitizen($citizenUser))<a class="outline-button" href="{{ $portal('bosesmoto/my-complaints/'.$data['item']->id.'/edit') }}"><span class="material-symbols-rounded">edit</span> Edit</a>@endif @if($data['item']->status === 'resolved')<form method="POST" action="{{ route('resident-portal.complaints.confirm-resolution',$data['item']) }}">@csrf<button class="primary-button" type="submit">Confirm resolution</button></form>@endif</div></article>

@elseif($screen === 'bosesmoto/submit')
    <a class="back-link" href="{{ $portal('bosesmoto/my-complaints') }}"><span class="material-symbols-rounded">arrow_back</span> My Complaints</a><x-resident-portal-page-header icon="add_location_alt" title="Submit Complaint" subtitle="Privately report a concern for city action." />
    <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.complaints.store') }}">@csrf<label>Title<input name="title" required maxlength="255"></label><label>Short summary<textarea name="short_summary" rows="2" maxlength="280" required></textarea></label><label>Full description<textarea name="description" rows="6" required></textarea></label><label>Category<select name="category_id" required>@foreach($data['categories'] as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label><label>Barangay<select name="barangay_id"><option value="">Select barangay</option>@foreach($data['barangays'] as $barangay)<option value="{{ $barangay->id }}">{{ $barangay->name }}</option>@endforeach</select></label><input type="hidden" name="latitude" data-latitude><input type="hidden" name="longitude" data-longitude><button type="button" class="outline-button" data-get-location><span class="material-symbols-rounded">my_location</span> Attach my location</button><button class="primary-button" type="submit">Submit complaint</button></form>

@elseif($screen === 'bosesmoto/polls')
    <a class="back-link" href="{{ $portal('bosesmoto') }}"><span class="material-symbols-rounded">arrow_back</span> BosesMoTo</a><x-resident-portal-page-header icon="poll" title="City Polls" subtitle="Vote on questions that affect the community." />@forelse($data['items'] as $item)<a class="native-card complaint-card" href="{{ $portal('bosesmoto/polls/'.$item->id) }}"><span class="status-chip {{ $item->isVoteOpen() ? 'success' : 'warning' }}">{{ $item->isVoteOpen() ? 'Voting open' : 'Closed' }}</span><strong>{{ $item->question }}</strong><p>{{ $item->description }}</p><small>{{ $item->votes_count }} votes</small></a>@empty<x-resident-portal-empty icon="poll" title="No polls available" message="Active city polls will appear here." />@endforelse{{ $data['items']->links() }}

@elseif(preg_match('#bosesmoto/polls/\d+#',$screen))
    <a class="back-link" href="{{ $portal('bosesmoto/polls') }}"><span class="material-symbols-rounded">arrow_back</span> Polls</a><article class="native-card detail-card"><span class="status-chip {{ $data['item']->isVoteOpen() ? 'success' : 'warning' }}">{{ $data['item']->isVoteOpen() ? 'Voting open' : 'Voting closed' }}</span><h1>{{ $data['item']->question }}</h1><p>{{ $data['item']->description }}</p><small>{{ $data['item']->votes_count }} total votes</small>@if($data['item']->isVoteOpen())<form class="poll-options" method="POST" action="{{ route('resident-portal.polls.vote',$data['item']) }}">@csrf @foreach($data['item']->options as $option)<label><input type="radio" name="option_id" value="{{ $option->id }}" required><span>{{ $option->option_text }}</span><small>{{ $option->votes_count }} votes</small></label>@endforeach<button class="primary-button" type="submit">Submit vote</button></form>@else<div class="poll-options">@foreach($data['item']->options as $option)<div><strong>{{ $option->option_text }}</strong><small>{{ $option->votes_count }} votes</small></div>@endforeach</div>@endif</article>

@elseif($screen === 'bosesmoto/community')
    <a class="back-link" href="{{ $portal('bosesmoto') }}"><span class="material-symbols-rounded">arrow_back</span> BosesMoTo</a><x-resident-portal-page-header icon="groups" title="Community Voice" subtitle="Share ideas and engage with residents." />
    <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.community.store') }}">@csrf<label>Share with the community<textarea name="body" rows="3" maxlength="5000" required placeholder="What is on your mind?"></textarea></label><label>Optional link<input type="url" name="external_url" placeholder="https://"></label><button class="primary-button" type="submit">Publish post</button></form>
    @forelse($data['items'] as $item)<article class="native-card community-post"><div class="post-author"><span class="avatar material-symbols-rounded filled">person</span><div><strong>{{ $item->author?->name ?? 'Resident' }}</strong><small>{{ $item->created_at->diffForHumans() }}</small></div>@if($item->is_pinned)<span class="status-chip warning">Pinned</span>@endif </div><p>{{ $item->body }}</p>@if($item->external_url)<a href="{{ $item->external_url }}" target="_blank" rel="noopener">{{ $item->external_url }}</a>@endif <div class="post-actions"><form method="POST" action="{{ route('resident-portal.community.react',$item) }}">@csrf<input type="hidden" name="reaction" value="like"><button type="submit"><span class="material-symbols-rounded filled">thumb_up</span>{{ $item->reactions_count }}</button></form><a href="{{ $portal('bosesmoto/community/'.$item->id.'/comments') }}"><span class="material-symbols-rounded filled">chat_bubble</span>{{ $item->comments_count }}</a></div></article>@empty<x-resident-portal-empty icon="groups" title="No community posts" message="Be the first resident to share an update." />@endforelse{{ $data['items']->links() }}

@elseif(preg_match('#bosesmoto/community/\d+/comments#',$screen))
    <a class="back-link" href="{{ $portal('bosesmoto/community') }}"><span class="material-symbols-rounded">arrow_back</span> Community</a><article class="native-card community-post"><div class="post-author"><span class="avatar material-symbols-rounded filled">person</span><div><strong>{{ $data['item']->author?->name }}</strong><small>{{ $data['item']->created_at->diffForHumans() }}</small></div></div><p>{{ $data['item']->body }}</p></article><p class="section-header">Comments</p>@forelse($data['comments'] as $comment)<div class="native-card comment"><strong>{{ $comment->author?->name ?? 'Resident' }}</strong><p>{{ $comment->body }}</p><small>{{ $comment->created_at->diffForHumans() }}</small></div>@empty<x-resident-portal-empty icon="chat_bubble" title="No comments yet" message="Start the conversation." />@endforelse <form class="native-card form-stack" method="POST" action="{{ route('resident-portal.community.comments.store',$data['item']) }}">@csrf<label>Add a comment<textarea name="body" rows="3" required></textarea></label><button class="primary-button" type="submit">Post comment</button></form>{{ $data['comments']->links() }}

@else
    <x-resident-portal-empty icon="construction" title="Page unavailable" message="This resident portal page could not be found." />
@endif
</div>
@endsection
