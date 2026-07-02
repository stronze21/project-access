<?php

namespace App\Services;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Collection;

class ComplaintSimilarityService
{
    public function findSimilar(string $title, ?string $summary = null, ?int $excludeComplaintId = null): Collection
    {
        $queryText = trim($title.' '.($summary ?? ''));
        $keywords = $this->extractKeywords($queryText);

        $query = Complaint::query()
            ->publicListing()
            ->with(['category:id,name', 'barangay:id,name'])
            ->select([
                'id',
                'reference_code',
                'title',
                'short_summary',
                'status',
                'support_count',
                'category_id',
                'barangay_id',
            ]);

        if ($excludeComplaintId !== null) {
            $query->where('id', '!=', $excludeComplaintId);
        }

        if (!empty($keywords)) {
            $query->where(function ($builder) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $builder->orWhere('title', 'like', '%'.$keyword.'%')
                        ->orWhere('short_summary', 'like', '%'.$keyword.'%');
                }
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->orderByDesc('support_count')
            ->latest('id')
            ->limit((int) config('complaints.similarity.max_results', 5))
            ->get();
    }

    /**
     * @return list<string>
     */
    private function extractKeywords(string $text): array
    {
        $tokens = preg_split('/[^a-z0-9]+/i', strtolower($text)) ?: [];
        $stopWords = [
            'the', 'and', 'for', 'with', 'that', 'this', 'from', 'your', 'have',
            'has', 'are', 'was', 'were', 'will', 'would', 'should', 'there', 'here',
            'where', 'when', 'what', 'why', 'how', 'about', 'into', 'than', 'then',
            'city', 'municipal', 'issue', 'complaint',
        ];

        $keywords = array_values(array_filter(
            array_unique($tokens),
            static fn (string $word): bool => strlen($word) >= 4 && !in_array($word, $stopWords, true)
        ));

        return array_slice($keywords, 0, 6);
    }
}
