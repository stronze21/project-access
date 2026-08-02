<?php

namespace App\Http\Controllers;

use App\Services\MobileAppReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MobileAppController extends Controller
{
    public function __construct(private readonly MobileAppReleaseService $releases)
    {
    }

    public function index()
    {
        return view('mobile-app.index', [
            'release' => $this->releases->release(),
        ]);
    }

    public function download()
    {
        $release = $this->releases->release();
        $path = $release['apk_path'] ?? null;

        if (! filled($path) || ! Storage::disk('public')->exists($path)) {
            abort(404, 'No APK release is currently available.');
        }

        $downloadName = $release['download_name'];

        return Storage::disk('public')->download($path, $downloadName, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => sprintf(
                'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                addcslashes($downloadName, '"\\'),
                rawurlencode($downloadName)
            ),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function release(): JsonResponse
    {
        $release = $this->releases->release();

        return response()->json([
            'name' => $release['name'],
            'version_name' => (string) $release['version_name'],
            'version_code' => (string) $release['version_code'],
            'release_notes' => $release['release_notes'],
            'apk_uploaded_at' => $release['apk_uploaded_at'],
            'has_apk' => $release['has_apk'],
            'download_page_url' => route('mobile-app.index'),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
