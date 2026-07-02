<?php

namespace App\Http\Controllers;

use App\Models\SentimentFollow;
use App\Models\User;
use App\Services\ComplaintAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SentimentFollowController extends Controller
{
    public function __construct(private ComplaintAuditLogger $auditLogger)
    {
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();
        abort_if((int) $viewer->id === (int) $user->id, 422, 'You cannot follow yourself.');

        SentimentFollow::query()->firstOrCreate([
            'follower_user_id' => $viewer->id,
            'followed_user_id' => $user->id,
        ]);

        $this->auditLogger->log('sentiment_user_followed', null, $user, $viewer, $request, [
            'followed_user_id' => $user->id,
        ]);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $viewer = $request->user();

        SentimentFollow::query()
            ->where('follower_user_id', $viewer->id)
            ->where('followed_user_id', $user->id)
            ->delete();

        $this->auditLogger->log('sentiment_user_unfollowed', null, $user, $viewer, $request, [
            'followed_user_id' => $user->id,
        ]);

        return back();
    }
}

