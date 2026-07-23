<?php

namespace App\Http\Controllers;

use App\Models\ResidentIdentityChangeRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResidentIdentityChangeRequestMediaController extends Controller
{
    public function __invoke(ResidentIdentityChangeRequest $identityRequest): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('edit-residents'), 403);
        abort_unless($identityRequest->type === 'photo' && $identityRequest->requested_file_path, 404);
        abort_unless(Storage::disk('local')->exists($identityRequest->requested_file_path), 404);

        return response()->file(Storage::disk('local')->path($identityRequest->requested_file_path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
