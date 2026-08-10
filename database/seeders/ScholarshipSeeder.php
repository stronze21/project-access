<?php

namespace Database\Seeders;

use App\Models\ScholarshipDocumentType;
use App\Models\ScholarshipProgram;
use Illuminate\Database\Seeder;

class ScholarshipSeeder extends Seeder
{
    public function run(): void
    {
        ScholarshipProgram::query()->updateOrCreate(
            ['name' => 'Alaminos City Scholarship Program (ACSP)'],
            [
                'academic_year' => '2026-2027',
                'description' => 'Online application for the Alaminos City Scholarship Program for Academic Year 2026-2027.',
                'open_at' => now()->subDay(),
                'close_at' => now()->addMonths(6),
                'office_address' => 'Mayor\'s Office, City Hall, Alaminos City, Pangasinan',
                'office_hours' => 'Monday–Friday, 8:00 AM – 5:00 PM',
                'required_originals' => [
                    'Accomplished application form',
                    'Certified Form 137A / registration form (as applicable)',
                    'Birth certificate (new applicants)',
                    '2x2 photo with name tag',
                    'True copy of grades (ongoing scholars)',
                    'Indigent certificate (new applicants)',
                    'Legal parent\'s ID',
                    'Photocopies of all requirements',
                ],
                'is_active' => true,
            ]
        );

        $documentTypes = [
            // Shared
            ['code' => 'accomplished_form', 'label' => 'Accomplished form', 'applicant_type' => 'both', 'sort_order' => 10],
            ['code' => 'photo_2x2', 'label' => '2x2 photo with name tag', 'applicant_type' => 'both', 'sort_order' => 20],
            ['code' => 'parent_id', 'label' => 'Legal parent\'s I.D.', 'applicant_type' => 'both', 'sort_order' => 30],
            ['code' => 'photocopies', 'label' => 'Photocopies of all requirements', 'applicant_type' => 'both', 'sort_order' => 100],

            // New applicants
            ['code' => 'form_137a', 'label' => 'Form 137A (Certified)', 'applicant_type' => 'new', 'sort_order' => 40],
            ['code' => 'birth_certificate', 'label' => 'Birth certificate', 'applicant_type' => 'new', 'sort_order' => 50],
            ['code' => 'form_137_or_registration', 'label' => 'Certified Form 137 or registration form', 'applicant_type' => 'new', 'sort_order' => 60],
            ['code' => 'indigent_certificate', 'label' => 'Indigent Certificate', 'applicant_type' => 'new', 'sort_order' => 70],

            // Ongoing scholars
            ['code' => 'registration_certified_x2', 'label' => 'Registration form (Certified x2)', 'applicant_type' => 'ongoing', 'sort_order' => 40],
            ['code' => 'true_copy_grades', 'label' => 'True copy of grades', 'applicant_type' => 'ongoing', 'sort_order' => 50],
            ['code' => 'latest_certificate', 'label' => 'Latest certificate', 'applicant_type' => 'ongoing', 'sort_order' => 60],
        ];

        foreach ($documentTypes as $type) {
            ScholarshipDocumentType::query()->updateOrCreate(
                [
                    'code' => $type['code'],
                    'applicant_type' => $type['applicant_type'],
                ],
                [
                    'label' => $type['label'],
                    'sort_order' => $type['sort_order'],
                    'is_required' => true,
                ]
            );
        }
    }
}
