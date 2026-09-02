<?php

namespace App\Http\Controllers;

use App\Models\DynamicForm;
use App\Services\DynamicForm\FormSubmissionService;
use Illuminate\Http\Request;

class DynamicFormExportController extends Controller
{
    public function __invoke(Request $request, DynamicForm $form, FormSubmissionService $submissions)
    {
        $user = $request->user();
        abort_unless($user->can('view-forms') || $user->can('process-forms'), 403);

        return $submissions->exportCsv($form, $user);
    }
}
