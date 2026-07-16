<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintBarangay;
use App\Models\ComplaintCategory;
use App\Models\Department;
use App\Models\PublicOfficial;
use App\Models\PublicServiceLink;
use App\Models\SosDepartment;
use App\Models\User;
use Illuminate\Contracts\View\View;

class ReferenceDataController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('manageReferenceData', Complaint::class);

        return view('complaints.references.index', [
            'counts' => [
                'categories' => ComplaintCategory::query()->count(),
                'barangays' => ComplaintBarangay::query()->count(),
                'departments' => Department::query()->count(),
                'action_officers' => User::query()->actionOfficers()->count(),
                'officials' => PublicOfficial::query()->count(),
                'sos_departments' => SosDepartment::query()->count(),
                'portal_links' => PublicServiceLink::query()->count(),
            ],
        ]);
    }
}
