<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\ResidentIdPrintBatch;
use App\Models\ResidentIdPrintBatchItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResidentIdCardController extends Controller
{
    public const MAX_BATCH_SIZE = 100;

    /**
     * Display the ID card for the specified resident in landscape orientation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showLandscape($id)
    {
        $resident = Resident::with('household')->findOrFail($id);

        return view('residents.id-card-landscape', [
            'resident' => $resident,
            'orientation' => 'landscape',
        ]);
    }

    /**
     * Generate a batch of ID cards for multiple residents.
     *
     * @return \Illuminate\Http\Response
     */
    public function generateBatch(Request $request)
    {
        $validated = $request->validate([
            'residents' => ['nullable', 'array', 'max:'.self::MAX_BATCH_SIZE, 'required_without:barangay'],
            'residents.*' => ['required', 'integer', 'distinct', 'exists:residents,id'],
            'barangay' => [
                'nullable',
                'string',
                'max:255',
                'required_without:residents',
                Rule::when(
                    $request->input('barangay') !== 'all',
                    Rule::exists('households', 'barangay')
                ),
            ],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
            'exclude_printed' => ['nullable', 'boolean'],
        ], [
            'residents.max' => 'Choose no more than '.self::MAX_BATCH_SIZE.' residents in one print batch.',
            'residents.*.distinct' => 'Each resident may appear only once in a print batch.',
            'residents.*.exists' => 'One or more selected residents no longer exist.',
        ]);

        $barangay = $validated['barangay'] ?? null;
        $status = $validated['status'] ?? 'all';
        $excludePrinted = (bool) ($validated['exclude_printed'] ?? true);
        $totalResidents = null;
        $openBatch = null;

        if ($barangay) {
            $scope = $this->residentScopeQuery($barangay, $status);
            $totalResidents = (clone $scope)->count();
            $excludedResidentIds = ResidentIdPrintBatchItem::query()
                ->whereNotNull('resident_id')
                ->when(
                    ! $excludePrinted,
                    fn ($items) => $items->whereHas('batch', fn ($batch) => $batch->where('status', 'generated'))
                )
                ->select('resident_id');
            $unassigned = (clone $scope)->whereNotIn('id', $excludedResidentIds);

            $openBatch = ResidentIdPrintBatch::query()
                ->where('barangay', $barangay)
                ->where('status_filter', $status)
                ->where('exclude_printed', $excludePrinted)
                ->where('status', 'generated')
                ->where('resident_count', '<', self::MAX_BATCH_SIZE)
                ->latest('batch_number')
                ->first();
            $availableSlots = self::MAX_BATCH_SIZE - ($openBatch?->resident_count ?? 0);
            $residents = $unassigned->limit($availableSlots)->get();

            if ($residents->isEmpty() && $openBatch) {
                return redirect()->route('residents.id-cards.batches.print', $openBatch);
            }

            if ($residents->isEmpty()) {
                $barangayLabel = $barangay === 'all' ? 'all barangays' : $barangay;
                throw ValidationException::withMessages([
                    'barangay' => "All matching residents for {$barangayLabel} are already assigned to tracked print batches.",
                ]);
            }

            $batchNumber = $openBatch?->batch_number ?? ((int) ResidentIdPrintBatch::query()
                ->where('barangay', $barangay)
                ->where('status_filter', $status)
                ->where('exclude_printed', $excludePrinted)
                ->max('batch_number')) + 1;
        } else {
            $batchNumber = 1;
            $residentIds = $validated['residents'];
            $residents = Resident::with('household')->whereIn('id', $residentIds)->get();
            $totalResidents = $residents->count();
        }

        $printBatch = DB::transaction(function () use ($request, $residents, $barangay, $status, $excludePrinted, $batchNumber, $totalResidents, $openBatch) {
            $batch = $openBatch ?? ResidentIdPrintBatch::create([
                'reference_number' => (string) Str::uuid(),
                'user_id' => $request->user()?->id,
                'barangay' => $barangay,
                'status_filter' => $status,
                'exclude_printed' => $excludePrinted,
                'batch_number' => $batchNumber,
                'total_matching' => $totalResidents,
                'resident_count' => $residents->count(),
                'status' => 'generated',
            ]);

            $batch->items()->createMany($residents->map(fn (Resident $resident) => [
                'resident_id' => $resident->id,
                'resident_pin' => $resident->resident_id,
                'resident_name' => $resident->full_name,
            ])->all());

            if ($openBatch) {
                $batch->update([
                    'user_id' => $request->user()?->id,
                    'total_matching' => $totalResidents,
                    'resident_count' => $batch->items()->count(),
                ]);
            }

            return $batch;
        });

        return redirect()->route('residents.id-cards.batches.print', $printBatch);
    }

    /**
     * Show the form for generating multiple ID cards.
     *
     * @return \Illuminate\Http\Response
     */
    public function batchForm()
    {
        $barangayList = \App\Models\Household::select('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->toArray();

        return view('residents.id-card-batch-form', [
            'barangayList' => $barangayList,
            'selectedBarangay' => request('barangay'),
            'selectedStatus' => request('status', 'active'),
            'excludePrinted' => request()->boolean('exclude_printed', true),
        ]);
    }

    public function batchHistory()
    {
        $printBatches = ResidentIdPrintBatch::with('user')
            ->latest()
            ->paginate(25);

        return view('residents.id-card-batch-history', compact('printBatches'));
    }

    public function showBatch(ResidentIdPrintBatch $printBatch)
    {
        $printBatch->load(['user', 'items.resident']);

        return view('residents.id-card-batch-show', compact('printBatch'));
    }

    public function printBatch(string $printBatch)
    {
        $printBatch = ResidentIdPrintBatch::findOrFail($printBatch);
        $printBatch->load(['items.resident.household']);
        $residents = $printBatch->items
            ->pluck('resident')
            ->filter()
            ->values();

        abort_if($residents->isEmpty(), 404, 'No current resident records remain in this print batch.');

        $hasNextBatch = false;
        if ($printBatch->barangay !== null && $printBatch->exclude_printed) {
            $excludedResidentIds = ResidentIdPrintBatchItem::query()
                ->whereNotNull('resident_id')
                ->when(
                    ! $printBatch->exclude_printed,
                    fn ($items) => $items->whereHas('batch', fn ($batch) => $batch->where('status', 'generated'))
                )
                ->select('resident_id');
            $hasNextBatch = $this->residentScopeQuery($printBatch->barangay, $printBatch->status_filter)
                ->whereNotIn('id', $excludedResidentIds)
                ->exists();
        }

        return view('residents.id-card-batch-landscape', [
            'residents' => $residents,
            'orientation' => 'landscape',
            'barangay' => $printBatch->barangay,
            'status' => $printBatch->status_filter,
            'batchNumber' => $printBatch->batch_number,
            'totalResidents' => $printBatch->total_matching,
            'hasNextBatch' => $hasNextBatch,
            'printBatch' => $printBatch,
        ]);
    }

    public function markBatchPrinted(Request $request, ResidentIdPrintBatch $printBatch): JsonResponse|RedirectResponse
    {
        $printedAt = now();

        DB::transaction(function () use ($request, $printBatch, $printedAt) {
            $printBatch->update([
                'user_id' => $request->user()?->id,
                'status' => 'print_initiated',
                'printed_at' => $printedAt,
            ]);
            $printBatch->items()->update(['printed_at' => $printedAt]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Print initiation recorded.',
                'reference_number' => $printBatch->reference_number,
                'printed_at' => $printedAt->toIso8601String(),
            ]);
        }

        return redirect()->route('residents.id-cards.batches.print', [
            'printBatch' => $printBatch,
            'print' => 1,
        ]);
    }

    /**
     * Redirect to the default ID card view (landscape).
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->showLandscape($id);
    }

    private function residentScopeQuery(string $barangay, string $status): Builder
    {
        return Resident::with('household')
            ->when(
                $barangay !== 'all',
                fn ($residents) => $residents->whereHas('household', fn ($household) => $household->where('barangay', $barangay))
            )
            ->when($status === 'active', fn ($residents) => $residents->where('is_active', true))
            ->when($status === 'inactive', fn ($residents) => $residents->where('is_active', false))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id');
    }
}
