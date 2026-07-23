<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use App\Models\Announcement;
use App\Models\CitizenServiceRequest;
use App\Models\CitizenServiceType;
use App\Models\Complaint;
use App\Models\ComplaintBarangay;
use App\Models\ComplaintCategory;
use App\Models\ComplaintComment;
use App\Models\ComplaintSupport;
use App\Models\Distribution;
use App\Models\EmergencyAlert;
use App\Models\GrievanceReport;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\PublicServiceLink;
use App\Models\Resident;
use App\Models\ResidentIdentityChangeRequest;
use App\Models\ResidentNotification;
use App\Models\SentimentComment;
use App\Models\SentimentPost;
use App\Models\SentimentReaction;
use App\Models\SosAlert;
use App\Models\SosDepartment;
use App\Models\SupportRequest;
use App\Services\ModuleSettings;
use App\Services\ResidentCitizenAccountService;
use App\Services\ResidentIdentityChangeRequestService;
use App\Services\SentimentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ResidentPortalController extends Controller
{
    public function __construct(
        private ResidentCitizenAccountService $citizenAccounts,
        private ModuleSettings $modules,
        private SentimentService $sentiments,
        private ResidentIdentityChangeRequestService $identityChanges,
    ) {}

    public function home(Request $request): View
    {
        return $this->renderScreen($request, 'home');
    }

    public function screen(Request $request, ?string $path = null): View
    {
        return $this->renderScreen($request, trim($path ?: 'home', '/'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],
            'emergency_contact_number' => ['nullable', 'string', 'max:20'],
        ]);

        $this->resident()->update($validated);

        return back()->with('status', 'Profile updated successfully.');
    }

    public function requestPhotoReplacement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $changeRequest = $this->identityChanges->submitPhoto($this->resident(), $validated['photo'], $validated['reason']);

        return back()->with('status', "Profile photo replacement request {$changeRequest->reference_number} submitted for verification.");
    }

    public function requestSignatureReplacement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:1500000'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $changeRequest = $this->identityChanges->submitSignature($this->resident(), $validated['signature'], $validated['reason']);

        return back()->with('status', "Signature replacement request {$changeRequest->reference_number} submitted for verification.");
    }

    public function storeSupport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', Rule::in(['account', 'privacy', 'technical', 'service-request', 'emergency', 'other'])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:4000'],
        ]);
        $resident = $this->resident();
        SupportRequest::create($validated + [
            'resident_id' => $resident->id, 'resident_identifier' => $resident->resident_id,
            'resident_name' => $resident->full_name, 'email' => $resident->email,
            'contact_number' => $resident->contact_number, 'source' => 'resident-web',
            'platform' => 'web', 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Support request received.');
    }

    public function storeAccountDeletion(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requested_action' => ['required', Rule::in(['delete-account-and-data', 'delete-app-data-only'])],
            'reason' => ['nullable', 'string', 'max:2000'],
            'retention_acknowledged' => ['accepted'],
        ]);
        $resident = $this->resident();
        AccountDeletionRequest::create([
            'resident_id' => $resident->id, 'resident_identifier' => $resident->resident_id,
            'resident_name' => $resident->full_name, 'email' => $resident->email,
            'contact_number' => $resident->contact_number, 'reason' => $validated['reason'] ?? null,
            'requested_action' => $validated['requested_action'], 'retention_acknowledged' => true,
            'source' => 'resident-web', 'platform' => 'web', 'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Account deletion request received.');
    }

    public function readNotification(ResidentNotification $notification): RedirectResponse
    {
        abort_unless($notification->resident_id === $this->resident()->id, 404);
        $notification->markAsRead();

        return back();
    }

    public function readAllNotifications(): RedirectResponse
    {
        ResidentNotification::where('resident_id', $this->resident()->id)->unread()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }

    public function storeService(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_type' => [
                'required',
                Rule::exists('citizen_service_types', 'code')->where('is_active', true),
            ],
            'service_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        CitizenServiceRequest::create($validated + [
            'resident_id' => $this->resident()->id,
            'status' => 'submitted',
            'current_step' => 'Application received',
        ]);

        return redirect('/resident-portal/citizen-services/tracking')->with('status', 'Service request submitted.');
    }

    public function storeGrievance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'location_label' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $photoPath = isset($validated['photo']) ? $validated['photo']->store('grievance-photos', 'public') : null;
        unset($validated['photo']);

        GrievanceReport::create($validated + ['resident_id' => $this->resident()->id, 'photo_path' => $photoPath]);

        return redirect('/resident-portal/citizen-services/grievances')->with('status', 'Your report was submitted.');
    }

    public function storeSos(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sos_department_id' => ['nullable', 'exists:sos_departments,id'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
            'location_label' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        SosAlert::create($validated + ['resident_id' => $this->resident()->id, 'status' => 'open']);

        return redirect('/resident-portal/citizen-services/sos/history')->with('status', 'Emergency SOS sent. Help has been notified.');
    }

    public function storeComplaint(Request $request): RedirectResponse
    {
        abort_unless($this->modules->enabled('bosesmoto') && $this->modules->enabled('complaints'), 404);
        $user = $this->citizenAccounts->resolve($this->resident());
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_summary' => ['required', 'string', 'max:280'],
            'description' => ['required', 'string', 'max:10000'],
            'category_id' => ['required', 'exists:complaint_categories,id'],
            'barangay_id' => ['nullable', 'exists:bosesmoto_barangays,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $complaint = Complaint::create($validated + [
            'reference_code' => 'BMT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'submitted_by_user_id' => $user->id,
            'reporter_name' => $user->name,
            'reporter_email' => $user->email,
            'visibility' => Complaint::VISIBILITY_PRIVATE,
            'is_anonymous_submission' => false,
            'status' => Complaint::STATUS_RECEIVED,
            'moderation_status' => Complaint::MODERATION_NORMAL,
            'submitted_ip' => $request->ip(),
        ]);

        return redirect('/resident-portal/bosesmoto/my-complaints/'.$complaint->id)->with('status', 'Complaint submitted successfully.');
    }

    public function supportComplaint(Request $request, Complaint $complaint): RedirectResponse
    {
        abort_unless($complaint->isPubliclyVisible(), 404);
        $user = $this->citizenAccounts->resolve($this->resident());

        DB::transaction(function () use ($complaint, $user): void {
            $existing = ComplaintSupport::query()->where('complaint_id', $complaint->id)->where('user_id', $user->id)->first();
            $existing ? $existing->delete() : ComplaintSupport::create(['complaint_id' => $complaint->id, 'user_id' => $user->id]);
            $complaint->update(['support_count' => $complaint->supports()->count()]);
        });

        return back()->with('status', 'Support updated.');
    }

    public function updateComplaint(Request $request, Complaint $complaint): RedirectResponse
    {
        $user = $this->citizenAccounts->resolve($this->resident());
        abort_unless($complaint->canBeEditedByCitizen($user), 403);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'short_summary' => ['required', 'string', 'max:280'],
            'description' => ['required', 'string', 'max:10000'], 'category_id' => ['required', 'exists:complaint_categories,id'],
            'barangay_id' => ['nullable', 'exists:bosesmoto_barangays,id'],
        ]);
        $complaint->update($validated + [
            'visibility' => Complaint::VISIBILITY_PRIVATE,
            'is_anonymous_submission' => false,
        ]);

        return redirect('/resident-portal/bosesmoto/my-complaints/'.$complaint->id)->with('status', 'Complaint updated.');
    }

    public function confirmComplaintResolution(Complaint $complaint): RedirectResponse
    {
        $user = $this->citizenAccounts->resolve($this->resident());
        abort_unless($complaint->submitted_by_user_id === $user->id && $complaint->status === Complaint::STATUS_RESOLVED, 403);
        $complaint->update(['status' => Complaint::STATUS_CLOSED, 'citizen_confirmed_at' => now(), 'closed_at' => now()]);

        return back()->with('status', 'Resolution confirmed. Complaint closed.');
    }

    public function commentComplaint(Request $request, Complaint $complaint): RedirectResponse
    {
        abort_unless($complaint->isPubliclyVisible(), 404);
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $user = $this->citizenAccounts->resolve($this->resident());
        ComplaintComment::create(['complaint_id' => $complaint->id, 'user_id' => $user->id, 'body' => $validated['body']]);

        return back()->with('status', 'Comment posted.');
    }

    public function vote(Request $request, Poll $poll): RedirectResponse
    {
        abort_unless($this->modules->enabled('polls'), 404);
        abort_unless($poll->isVoteOpen(), 422);
        $validated = $request->validate(['option_id' => ['required', Rule::exists('poll_options', 'id')->where('poll_id', $poll->id)]]);
        $user = $this->citizenAccounts->resolve($this->resident());

        PollVote::updateOrCreate(['poll_id' => $poll->id, 'user_id' => $user->id], ['poll_option_id' => $validated['option_id']]);

        return back()->with('status', 'Your vote has been recorded.');
    }

    public function storePost(Request $request): RedirectResponse
    {
        abort_unless($this->modules->enabled('sentiments'), 404);
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000'], 'external_url' => ['nullable', 'url', 'max:2048']]);
        $user = $this->citizenAccounts->resolve($this->resident());
        abort_if($this->sentiments->isPostingBanned($user), 403);

        SentimentPost::create([
            'user_id' => $user->id,
            'body' => trim($validated['body']),
            'media_kind' => empty($validated['external_url']) ? SentimentPost::MEDIA_NONE : SentimentPost::MEDIA_EXTERNAL,
            'external_url' => $validated['external_url'] ?? null,
        ]);

        return back()->with('status', 'Post published.');
    }

    public function commentPost(Request $request, SentimentPost $post): RedirectResponse
    {
        $user = $this->citizenAccounts->resolve($this->resident());
        abort_unless($this->sentiments->canViewPost($user, $post), 404);
        abort_if($post->is_comments_locked, 422);
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        SentimentComment::create(['post_id' => $post->id, 'user_id' => $user->id, 'body' => trim($validated['body'])]);

        return back()->with('status', 'Comment posted.');
    }

    public function reactPost(Request $request, SentimentPost $post): RedirectResponse
    {
        $user = $this->citizenAccounts->resolve($this->resident());
        abort_unless($this->sentiments->canViewPost($user, $post), 404);
        $validated = $request->validate(['reaction' => ['required', Rule::in($this->sentiments->reactionKeys())]]);
        $reaction = SentimentReaction::query()->where([
            'user_id' => $user->id,
            'reactionable_type' => SentimentPost::class,
            'reactionable_id' => $post->id,
        ])->first();

        if ($reaction?->reaction === $validated['reaction']) {
            $reaction->delete();
        } else {
            SentimentReaction::updateOrCreate([
                'user_id' => $user->id,
                'reactionable_type' => SentimentPost::class,
                'reactionable_id' => $post->id,
            ], ['reaction' => $validated['reaction']]);
        }

        return back();
    }

    private function renderScreen(Request $request, string $screen): View
    {
        $resident = $this->resident()->load(['household', 'sourceIncomeType']);
        $citizenUser = str_starts_with($screen, 'bosesmoto') ? $this->citizenAccounts->resolve($resident) : null;

        return view('resident-portal.screen', [
            'screen' => $screen,
            'resident' => $resident,
            'citizenUser' => $citizenUser,
            'data' => $this->screenData($request, $screen, $resident, $citizenUser),
            'moduleState' => collect($this->modules->all())->mapWithKeys(fn ($value, $key) => [$key => (bool) $value['enabled']])->all(),
            'qrSvg' => in_array($screen, ['home', 'digital-id'], true)
                ? QrCode::format('svg')->size(220)->margin(1)->errorCorrection('H')->generate($resident->generateQrCode())
                : null,
        ]);
    }

    private function screenData(Request $request, string $screen, Resident $resident, $citizenUser): array
    {
        if ($screen === 'home') {
            return [
                'upcoming' => Distribution::where('resident_id', $resident->id)->whereIn('status', ['pending', 'verified'])->with('ayudaProgram')->latest('distribution_date')->take(3)->get(),
                'announcements' => Announcement::published()->latest('published_at')->take(3)->get(),
            ];
        }

        if ($screen === 'digital-id') {
            $resident->generateQrCode();

            return [];
        }

        if ($screen === 'household') {
            abort_unless($resident->household_id, 404);

            return [
                'household' => $resident->household,
                'members' => Resident::query()
                    ->where('household_id', $resident->household_id)
                    ->where('is_active', true)
                    ->orderByRaw("CASE relationship_to_head WHEN 'head' THEN 0 WHEN 'spouse' THEN 1 ELSE 2 END")
                    ->orderBy('first_name')
                    ->get(),
            ];
        }

        if ($screen === 'ayuda') {
            return [
                'items' => Distribution::where('resident_id', $resident->id)->with(['ayudaProgram', 'batch'])->latest('distribution_date')->paginate(10),
                'received' => Distribution::where('resident_id', $resident->id)->where('status', 'distributed')->count(),
                'total' => Distribution::where('resident_id', $resident->id)->where('status', 'distributed')->sum('amount'),
            ];
        }

        if (str_starts_with($screen, 'announcements')) {
            if (preg_match('#announcements/(\d+)#', $screen, $matches)) {
                return ['item' => Announcement::published()->findOrFail((int) $matches[1])];
            }

            return [
                'items' => Announcement::published()->latest('is_pinned')->latest('published_at')->paginate(10),
                'notifications' => ResidentNotification::where('resident_id', $resident->id)->latest()->take(20)->get(),
                'unread' => ResidentNotification::where('resident_id', $resident->id)->unread()->count(),
            ];
        }

        if ($screen === 'support') {
            return ['items' => SupportRequest::where('resident_id', $resident->id)->latest('submitted_at')->paginate(10)];
        }

        if ($screen === 'account-deletion') {
            return ['items' => AccountDeletionRequest::where('resident_id', $resident->id)->latest('submitted_at')->paginate(10)];
        }

        if ($screen === 'settings') {
            return [
                'identityRequests' => ResidentIdentityChangeRequest::where('resident_id', $resident->id)
                    ->latest()->take(10)->get(),
            ];
        }

        if ($screen === 'citizen-services/tracking') {
            return ['items' => CitizenServiceRequest::where('resident_id', $resident->id)->latest('status_updated_at')->paginate(10)];
        }

        if ($screen === 'citizen-services/tracking/new') {
            return [
                'serviceTypes' => CitizenServiceType::where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
            ];
        }

        if (preg_match('#citizen-services/tracking/(\d+)#', $screen, $matches)) {
            return ['item' => CitizenServiceRequest::where('resident_id', $resident->id)->findOrFail((int) $matches[1])];
        }

        if ($screen === 'citizen-services/public-services') {
            return ['items' => PublicServiceLink::where('is_active', true)->orderBy('sort_order')->get()->groupBy('service_type')];
        }

        if ($screen === 'citizen-services/grievances') {
            return ['items' => GrievanceReport::where('resident_id', $resident->id)->latest()->paginate(10)];
        }

        if (preg_match('#citizen-services/grievances/(\d+)#', $screen, $matches)) {
            return ['item' => GrievanceReport::where('resident_id', $resident->id)->findOrFail((int) $matches[1])];
        }

        if ($screen === 'citizen-services/alerts') {
            return ['items' => EmergencyAlert::active()->latest('starts_at')->paginate(10)];
        }

        if (preg_match('#citizen-services/alerts/(\d+)#', $screen, $matches)) {
            return ['item' => EmergencyAlert::active()->findOrFail((int) $matches[1])];
        }

        if ($screen === 'citizen-services/sos') {
            return ['departments' => SosDepartment::where('is_active', true)->orderBy('sort_order')->get()];
        }

        if ($screen === 'citizen-services/sos/history') {
            return ['items' => SosAlert::where('resident_id', $resident->id)->with('department')->latest()->paginate(10)];
        }

        if ($screen === 'bosesmoto/complaints') {
            abort(404);
        }

        if ($screen === 'bosesmoto/my-complaints') {
            return ['items' => Complaint::where('submitted_by_user_id', $citizenUser->id)->with(['category', 'barangay'])->latest()->paginate(10)];
        }

        if (preg_match('#^bosesmoto/my-complaints/(\d+)/edit$#', $screen, $matches)) {
            $complaint = Complaint::where('submitted_by_user_id', $citizenUser->id)->findOrFail((int) $matches[1]);
            abort_unless($complaint->canBeEditedByCitizen($citizenUser), 403);

            return ['item' => $complaint, 'categories' => ComplaintCategory::where('is_active', true)->orderBy('name')->get(), 'barangays' => ComplaintBarangay::where('is_active', true)->orderBy('name')->get()];
        }

        if (preg_match('#bosesmoto/my-complaints/(\d+)#', $screen, $matches)) {
            $complaint = Complaint::with(['category', 'barangay', 'submitter'])
                ->where('submitted_by_user_id', $citizenUser->id)
                ->findOrFail((int) $matches[1]);

            return ['item' => $complaint];
        }

        if ($screen === 'bosesmoto/submit') {
            return ['categories' => ComplaintCategory::where('is_active', true)->orderBy('name')->get(), 'barangays' => ComplaintBarangay::where('is_active', true)->orderBy('name')->get()];
        }

        if ($screen === 'bosesmoto/polls') {
            return ['items' => Poll::withCount('votes')->latest()->paginate(10)];
        }

        if (preg_match('#bosesmoto/polls/(\d+)#', $screen, $matches)) {
            return ['item' => Poll::with(['options' => fn ($query) => $query->withCount('votes')])->withCount('votes')->findOrFail((int) $matches[1])];
        }

        if ($screen === 'bosesmoto/community') {
            return ['items' => SentimentPost::visibleTo($citizenUser)->with('author')->withCount(['comments', 'reactions'])->latest()->paginate(10)];
        }

        if (preg_match('#bosesmoto/community/(\d+)/comments#', $screen, $matches)) {
            $post = SentimentPost::visibleTo($citizenUser)->with('author')->findOrFail((int) $matches[1]);

            return ['item' => $post, 'comments' => SentimentComment::visibleTo($citizenUser)->where('post_id', $post->id)->with('author')->latest()->paginate(20)];
        }

        return [];
    }

    private function resident(): Resident
    {
        /** @var Resident $resident */
        $resident = Auth::guard('resident')->user();

        return $resident;
    }
}
