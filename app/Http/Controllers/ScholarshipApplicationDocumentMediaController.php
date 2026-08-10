<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipApplicationDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipApplicationDocumentMediaController extends Controller
{
    public function __invoke(ScholarshipApplicationDocument $document): StreamedResponse
    {
        abort_unless(auth()->user()?->can('manage-scholarship-applications') || auth()->user()?->can('view-scholarship-applications'), 403);
        abort_unless(Storage::disk($document->storage_disk)->exists($document->storage_path), 404);

        return Storage::disk($document->storage_disk)->download($document->storage_path, $document->original_name);
    }
}
