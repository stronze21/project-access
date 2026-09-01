<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipApplicationReportController extends Controller
{
    private const HEADERS = [
        'Applicant Type',
        'Last name',
        'First name',
        'Middle name',
        'Age',
        'Civil Status',
        'Gender',
        'Date of Birth',
        'Place of Birth',
        'Address',
        'Course',
        'Contact Number',
        'Email Address',
        'Name of Father',
        'Occupation',
        'Name of Mother',
        'Occupation',
        'Emergency contact name',
        'Emergency contact number',
    ];

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:all,draft,submitted,under_review,needs_resubmission,conditionally_approved,awarded,rejected'],
            'applicant_type' => ['nullable', 'in:all,new,ongoing'],
            'program_id' => ['nullable', 'integer', 'exists:scholarship_programs,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = ScholarshipApplication::query()
            ->with(['resident.household'])
            ->when(($filters['status'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(($filters['applicant_type'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('applicant_type', $filters['applicant_type']))
            ->when(isset($filters['program_id']), fn (Builder $query) => $query->where('scholarship_program_id', $filters['program_id']))
            ->when(trim($filters['search'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $term = '%'.trim($filters['search']).'%';
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('reference_number', 'like', $term)
                        ->orWhereHas('resident', fn (Builder $resident) => $resident
                            ->where('resident_id', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term));
                });
            })
            ->oldest('id');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, self::HEADERS);

            $query->chunkById(500, function ($applications) use ($output): void {
                foreach ($applications as $application) {
                    $resident = $application->resident;
                    $row = [
                        $application->applicant_type === ScholarshipApplication::APPLICANT_ONGOING ? 'old' : 'new',
                        $resident?->last_name,
                        $resident?->first_name,
                        $resident?->middle_name,
                        $resident?->getAge(),
                        $resident?->civil_status,
                        $resident?->gender,
                        $resident?->birthDateIso(),
                        $resident?->birthplace,
                        $resident?->household?->full_address,
                        $application->course,
                        $resident?->contact_number,
                        $resident?->email,
                        $application->father_name,
                        $application->father_occupation,
                        $application->mother_name,
                        $application->mother_occupation,
                        $resident?->emergency_contact_name,
                        $resident?->emergency_contact_number,
                    ];

                    fputcsv($output, array_map($this->safeCell(...), $row));
                }
            });

            fclose($output);
        }, 'scholarship-report-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function safeCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
