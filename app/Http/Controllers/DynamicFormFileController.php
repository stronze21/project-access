<?php

namespace App\Http\Controllers;

use App\Models\DynamicFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DynamicFormFileController extends Controller
{
    public function __invoke(Request $request, DynamicFormSubmission $submission, string $fieldKey): StreamedResponse
    {
        abort_unless($submission->visibleTo($request->user()), 403);

        $file = $submission->answerFor($fieldKey);
        abort_unless(is_array($file) && ! empty($file['path']), 404);

        $disk = $file['disk'] ?? 'local';
        abort_unless(Storage::disk($disk)->exists($file['path']), 404);

        return Storage::disk($disk)->download($file['path'], $file['name'] ?? basename($file['path']));
    }
}
