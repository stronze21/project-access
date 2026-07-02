<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Services\AttachmentVirusScanner;
use App\Services\ComplaintAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplaintAttachmentController extends Controller
{
    public function __construct(
        private AttachmentVirusScanner $virusScanner,
        private ComplaintAuditLogger $auditLogger
    ) {
    }

    public function store(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('uploadAttachment', $complaint);

        $validated = $request->validate([
            'type' => ['required', Rule::in([ComplaintAttachment::TYPE_EVIDENCE, ComplaintAttachment::TYPE_RESOLUTION])],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'file',
                'max:'.(int) config('complaints.attachments.max_size_kb', 20480),
                'mimetypes:'.implode(',', config('complaints.attachments.allowed_mime_types', [])),
            ],
        ]);

        $files = $validated['files'];
        $maxFiles = (int) config('complaints.attachments.max_files_per_complaint', 5);
        $currentCount = $complaint->attachments()->count();

        if (($currentCount + count($files)) > $maxFiles) {
            return back()->withErrors([
                'files' => "Attachment limit exceeded. Max {$maxFiles} files per complaint.",
            ]);
        }

        $rejected = [];

        /** @var UploadedFile $file */
        foreach ($files as $file) {
            $path = $file->store('complaints/'.$complaint->id, 'local');
            $scan = $this->virusScanner->scan($file);

            if ($scan['status'] !== ComplaintAttachment::SCAN_CLEAN) {
                Storage::disk('local')->delete($path);
                $rejected[] = $file->getClientOriginalName();
                continue;
            }

            $attachment = $complaint->attachments()->create([
                'uploaded_by_user_id' => $request->user()->id,
                'type' => $validated['type'],
                'storage_disk' => 'local',
                'storage_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => (int) $file->getSize(),
                'virus_scan_status' => $scan['status'],
                'virus_scan_message' => $scan['message'],
                'scanned_at' => Carbon::now(),
            ]);

            $this->auditLogger->log(
                'attachment_uploaded',
                $complaint,
                $attachment,
                $request->user(),
                $request,
                ['type' => $validated['type']]
            );
        }

        if (!empty($rejected)) {
            return back()->withErrors([
                'files' => 'Some files were rejected by virus scan: '.implode(', ', $rejected),
            ]);
        }

        return back()->with('status', 'Attachment(s) uploaded.');
    }

    public function download(Request $request, Complaint $complaint, ComplaintAttachment $attachment): StreamedResponse
    {
        abort_unless((int) $attachment->complaint_id === (int) $complaint->id, 404);
        $this->authorize('downloadAttachment', $complaint);

        $this->auditLogger->log('attachment_downloaded', $complaint, $attachment, $request->user(), $request);

        return Storage::disk($attachment->storage_disk)->download($attachment->storage_path, $attachment->original_name);
    }
}
