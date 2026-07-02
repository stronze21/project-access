<?php

namespace App\Http\Controllers;

use App\Models\SentimentComment;
use App\Models\SentimentPost;
use App\Models\SentimentReport;
use App\Services\ComplaintAuditLogger;
use App\Services\SentimentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SentimentReportController extends Controller
{
    public function __construct(
        private SentimentService $sentimentService,
        private ComplaintAuditLogger $auditLogger
    ) {
    }

    public function reportPost(Request $request, SentimentPost $post): RedirectResponse
    {
        abort_unless($this->sentimentService->canViewPost($request->user(), $post), 404);
        abort_if((int) $post->user_id === (int) $request->user()->id, 422, 'You cannot report your own post.');

        return $this->storeReport($request, $post, [
            'post_id' => $post->id,
        ]);
    }

    public function reportComment(Request $request, SentimentComment $comment): RedirectResponse
    {
        abort_unless($this->sentimentService->canViewComment($request->user(), $comment), 404);
        abort_if((int) $comment->user_id === (int) $request->user()->id, 422, 'You cannot report your own comment.');

        return $this->storeReport($request, $comment, [
            'post_id' => $comment->post_id,
            'comment_id' => $comment->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function storeReport(Request $request, Model $reportable, array $metadata): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = trim((string) ($validated['reason'] ?? ''));

        $report = SentimentReport::query()->firstOrNew([
            'reporter_user_id' => $request->user()->id,
            'reportable_type' => $reportable::class,
            'reportable_id' => $reportable->getKey(),
        ]);

        $alreadyOpen = $report->exists && $report->status === SentimentReport::STATUS_OPEN;

        $report->status = SentimentReport::STATUS_OPEN;
        $report->reason = $reason !== '' ? $reason : null;
        $report->reviewed_by_user_id = null;
        $report->reviewed_at = null;
        $report->save();

        $openReportCount = SentimentReport::query()
            ->where('reportable_type', $reportable::class)
            ->where('reportable_id', $reportable->getKey())
            ->where('status', SentimentReport::STATUS_OPEN)
            ->count();

        $reportable->reports_count = $openReportCount;
        $autoHidden = false;
        if ($openReportCount >= (int) config('sentiments.report_auto_hide_threshold', 10) && $reportable->hidden_at === null) {
            $reportable->hidden_at = now();
            $reportable->hidden_reason = 'auto_hidden_reports';
            $autoHidden = true;
        }
        $reportable->save();

        $this->auditLogger->log('sentiment_report_submitted', null, $reportable, $request->user(), $request, array_merge($metadata, [
            'reason' => $reason,
            'open_reports_count' => $openReportCount,
        ]));

        if ($autoHidden) {
            $this->auditLogger->log('sentiment_auto_hidden', null, $reportable, null, $request, array_merge($metadata, [
                'trigger' => 'report_threshold',
                'threshold' => (int) config('sentiments.report_auto_hide_threshold', 10),
                'open_reports_count' => $openReportCount,
            ]));
        }

        if ($alreadyOpen) {
            return back()->with('status', 'Report already submitted. Thank you.');
        }

        return back()->with('status', 'Report submitted. Thank you.');
    }
}

