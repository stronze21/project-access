<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $types = [
            ['code' => 'accomplished_form', 'label' => 'Accomplished form', 'applicant_type' => 'both', 'sort_order' => 10],
            ['code' => 'photo_2x2', 'label' => '2x2 photo with name tag', 'applicant_type' => 'both', 'sort_order' => 20],
            ['code' => 'parent_id', 'label' => "Legal parent's I.D.", 'applicant_type' => 'both', 'sort_order' => 30],
            ['code' => 'photocopies', 'label' => 'Photocopies of all requirements', 'applicant_type' => 'both', 'sort_order' => 100],
            ['code' => 'form_137a', 'label' => 'Form 137A (Certified)', 'applicant_type' => 'new', 'sort_order' => 40],
            ['code' => 'birth_certificate', 'label' => 'Birth certificate', 'applicant_type' => 'new', 'sort_order' => 50],
            ['code' => 'form_137_or_registration', 'label' => 'Certified Form 137 or registration form', 'applicant_type' => 'new', 'sort_order' => 60],
            ['code' => 'indigent_certificate', 'label' => 'Indigent Certificate', 'applicant_type' => 'new', 'sort_order' => 70],
            ['code' => 'registration_certified_x2', 'label' => 'Registration form (Certified x2)', 'applicant_type' => 'ongoing', 'sort_order' => 40],
            ['code' => 'true_copy_grades', 'label' => 'True copy of grades', 'applicant_type' => 'ongoing', 'sort_order' => 50],
            ['code' => 'latest_certificate', 'label' => 'Latest certificate', 'applicant_type' => 'ongoing', 'sort_order' => 60],
        ];

        foreach ($types as $type) {
            DB::table('scholarship_document_types')->insertOrIgnore(
                $type + ['is_required' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        // Keep administrator-managed checklist data when rolling back application code.
    }
};
