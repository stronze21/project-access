<?php

namespace App\Services\DynamicForm;

use App\Models\ComplaintAttachment;
use App\Models\DynamicForm;
use App\Models\DynamicFormSubmission;
use App\Models\DynamicFormSubmissionEvent;
use App\Models\Resident;
use App\Models\User;
use App\Services\AttachmentVirusScanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionService
{
    public function __construct(
        private AttachmentVirusScanner $virusScanner,
        private FormWorkflowService $workflow,
    ) {}

    /**
     * @param  array<string, mixed>  $answers
     * @param  array<string, UploadedFile|null>  $uploads
     */
    public function create(
        DynamicForm $form,
        array $answers,
        array $uploads = [],
        ?User $actor = null,
        ?int $residentId = null,
        bool $submit = false,
    ): DynamicFormSubmission {
        $form->load(['fields', 'statuses']);

        $this->assertFillable($form);

        $initial = $form->initialStatus();
        if (! $initial) {
            throw ValidationException::withMessages([
                'status' => 'This form has no initial workflow status.',
            ]);
        }

        $schema = $form->schemaSnapshot();
        $normalized = $this->validateAndNormalize($form, $schema, $answers, $uploads, $residentId);

        return DB::transaction(function () use ($form, $schema, $normalized, $actor, $initial, $submit) {
            $submission = DynamicFormSubmission::create([
                'dynamic_form_id' => $form->id,
                'status_id' => $initial->id,
                'resident_id' => $normalized['resident_id'],
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
                'schema_snapshot' => $schema,
                'answers' => $normalized['answers'],
            ]);

            $this->syncProjection($submission, $schema, $normalized['answers']);

            DynamicFormSubmissionEvent::create([
                'dynamic_form_submission_id' => $submission->id,
                'from_status_id' => null,
                'to_status_id' => $initial->id,
                'actor_id' => $actor?->id,
                'note' => 'Submission created.',
            ]);

            if ($submit) {
                $this->workflow->submitFromInitial($submission->fresh(['status', 'form.statuses', 'form.transitions']), $actor);
            }

            return $submission->fresh(['status', 'tags', 'resident', 'values']);
        });
    }

    /**
     * @param  array<string, mixed>  $answers
     * @param  array<string, UploadedFile|null>  $uploads
     */
    public function update(
        DynamicFormSubmission $submission,
        array $answers,
        array $uploads = [],
        ?User $actor = null,
        ?int $residentId = null,
        bool $submit = false,
    ): DynamicFormSubmission {
        $submission->load(['form.fields', 'form.statuses', 'status']);

        if (! $submission->isInInitialStatus()) {
            throw ValidationException::withMessages([
                'answers' => 'Only submissions in the initial status can be edited.',
            ]);
        }

        $form = $submission->form;
        $schema = $form->schemaSnapshot();
        $normalized = $this->validateAndNormalize(
            $form,
            $schema,
            $answers,
            $uploads,
            $residentId,
            $submission->answers ?? [],
        );

        return DB::transaction(function () use ($submission, $schema, $normalized, $actor, $submit) {
            $submission->schema_snapshot = $schema;
            $submission->answers = $normalized['answers'];
            $submission->resident_id = $normalized['resident_id'];
            $submission->updated_by = $actor?->id;
            $submission->save();

            $this->syncProjection($submission, $schema, $normalized['answers']);

            if ($submit) {
                $this->workflow->submitFromInitial($submission->fresh(['status', 'form.statuses', 'form.transitions']), $actor);
            }

            return $submission->fresh(['status', 'tags', 'resident', 'values']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $schema
     * @param  array<string, mixed>  $answers
     */
    public function syncProjection(DynamicFormSubmission $submission, array $schema, array $answers): void
    {
        $submission->values()->delete();

        foreach ($schema as $field) {
            if (empty($field['is_filterable'])) {
                continue;
            }

            $key = $field['key'];
            $value = $answers[$key] ?? null;
            $row = [
                'field_key' => $key,
                'value_string' => null,
                'value_number' => null,
                'value_date' => null,
            ];

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $type = $field['type'] ?? 'short_text';

            if ($type === 'number') {
                $row['value_number'] = is_numeric($value) ? $value : null;
                $row['value_string'] = (string) $value;
            } elseif ($type === 'date') {
                $row['value_date'] = $value;
                $row['value_string'] = (string) $value;
            } elseif (is_array($value)) {
                if (isset($value['name']) || isset($value['id'])) {
                    $row['value_string'] = (string) ($value['label'] ?? $value['name'] ?? $value['id'] ?? '');
                } else {
                    $row['value_string'] = implode(', ', array_map('strval', $value));
                }
            } elseif (is_bool($value)) {
                $row['value_string'] = $value ? 'yes' : 'no';
            } else {
                $row['value_string'] = (string) $value;
            }

            if (is_string($row['value_string'])) {
                $row['value_string'] = mb_substr($row['value_string'], 0, 255);
            }

            $submission->values()->create($row);
        }
    }

    public function exportCsv(DynamicForm $form, ?User $actor = null): StreamedResponse
    {
        $form->load(['fields', 'statuses']);
        $fields = $form->fields()->orderBy('sort_order')->get();

        $query = $form->submissions()
            ->with(['status', 'tags', 'resident', 'creator'])
            ->orderByDesc('created_at');

        if ($actor && ! $actor->can('view-forms') && ! $actor->can('process-forms')) {
            $query->where('created_by', $actor->id);
        }

        $filename = 'form-'.$form->slug.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query, $fields) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $headers = [
                'Reference',
                'Status',
                'Tags',
                'Resident',
                'Created by',
                'Created at',
            ];

            foreach ($fields as $field) {
                $headers[] = $field->label.($field->is_filterable ? ' [filterable]' : '');
            }

            fputcsv($handle, $headers);

            $query->chunk(100, function ($submissions) use ($handle, $fields) {
                foreach ($submissions as $submission) {
                    $row = [
                        $submission->reference_number,
                        $submission->status?->label,
                        $submission->tags->pluck('label')->implode(', '),
                        $submission->resident?->full_name ?? $submission->resident?->resident_id,
                        $submission->creator?->name,
                        optional($submission->created_at)?->timezone('Asia/Manila')->format('Y-m-d H:i'),
                    ];

                    $answers = $submission->answers ?? [];
                    foreach ($fields as $field) {
                        $row[] = $this->flattenAnswer($answers[$field->key] ?? null, $field->type);
                    }

                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $schema
     * @param  array<string, mixed>  $answers
     * @param  array<string, UploadedFile|null>  $uploads
     * @param  array<string, mixed>  $existingAnswers
     * @return array{answers: array<string, mixed>, resident_id: ?int}
     */
    public function validateAndNormalize(
        DynamicForm $form,
        array $schema,
        array $answers,
        array $uploads = [],
        ?int $residentId = null,
        array $existingAnswers = [],
    ): array {
        $errors = [];
        $normalized = [];

        foreach ($schema as $field) {
            $key = $field['key'];
            $type = $field['type'];
            $label = $field['label'];
            $value = $answers[$key] ?? null;
            $upload = $uploads[$key] ?? null;

            if ($type === 'file') {
                if ($upload instanceof UploadedFile) {
                    $normalized[$key] = $this->storeUpload($form, $key, $upload);
                } elseif (isset($existingAnswers[$key]) && is_array($existingAnswers[$key])) {
                    $normalized[$key] = $existingAnswers[$key];
                } elseif (! empty($field['is_required'])) {
                    $errors["answers.{$key}"] = "{$label} is required.";
                } else {
                    $normalized[$key] = null;
                }

                continue;
            }

            if ($type === 'checkboxes') {
                $value = is_array($value) ? array_values(array_filter($value, fn ($item) => $item !== null && $item !== '')) : [];
            }

            if ($type === 'yes_no') {
                if ($value === true || $value === '1' || $value === 1 || $value === 'yes') {
                    $value = 'yes';
                } elseif ($value === false || $value === '0' || $value === 0 || $value === 'no') {
                    $value = 'no';
                } elseif ($value === null || $value === '') {
                    $value = null;
                }
            }

            if ($type === 'number' && $value !== null && $value !== '' && ! is_numeric($value)) {
                $errors["answers.{$key}"] = "{$label} must be a number.";
            }

            if ($type === 'date' && $value !== null && $value !== '' && strtotime((string) $value) === false) {
                $errors["answers.{$key}"] = "{$label} must be a valid date.";
            }

            if (in_array($type, DynamicForm::OPTION_TYPES, true) && $value !== null && $value !== [] && $value !== '') {
                $allowed = collect($field['options'] ?? [])->pluck('value')->all();
                $given = is_array($value) ? $value : [$value];
                foreach ($given as $item) {
                    if (! in_array((string) $item, $allowed, true)) {
                        $errors["answers.{$key}"] = "{$label} has an invalid option.";
                        break;
                    }
                }
            }

            if ($type === 'resident' && $value) {
                if (! Resident::query()->whereKey($value)->exists()) {
                    $errors["answers.{$key}"] = "{$label} must be an existing resident.";
                }
            }

            $isEmpty = $value === null || $value === '' || $value === [];
            if (! empty($field['is_required']) && $isEmpty) {
                $errors["answers.{$key}"] = "{$label} is required.";
            }

            $normalized[$key] = $isEmpty ? ($type === 'checkboxes' ? [] : null) : $value;
        }

        if ($form->link_to_resident) {
            if (! $residentId || ! Resident::query()->whereKey($residentId)->exists()) {
                $errors['resident_id'] = 'Link this submission to a resident.';
            }
        } else {
            $residentId = $residentId ?: null;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'answers' => $normalized,
            'resident_id' => $residentId,
        ];
    }

    private function storeUpload(DynamicForm $form, string $key, UploadedFile $file): array
    {
        $scan = $this->virusScanner->scan($file);
        if (($scan['status'] ?? null) === ComplaintAttachment::SCAN_INFECTED) {
            throw ValidationException::withMessages([
                "uploads.{$key}" => 'The uploaded file did not pass the virus scan.',
            ]);
        }

        $path = $file->store('dynamic-forms/'.$form->id.'/'.$key, 'local');

        return [
            'disk' => 'local',
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'virus_scan_status' => $scan['status'] ?? ComplaintAttachment::SCAN_CLEAN,
        ];
    }

    private function flattenAnswer(mixed $value, string $type): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($type === 'file' && is_array($value)) {
            return (string) ($value['name'] ?? $value['path'] ?? '');
        }

        if ($type === 'resident') {
            $resident = Resident::query()->find($value);

            return $resident ? trim($resident->first_name.' '.$resident->last_name).' ('.$resident->resident_id.')' : (string) $value;
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return implode(', ', array_map('strval', $value));
            }

            return (string) ($value['label'] ?? $value['name'] ?? json_encode($value));
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return (string) $value;
    }

    private function assertFillable(DynamicForm $form): void
    {
        if (! $form->is_active) {
            throw ValidationException::withMessages([
                'form' => 'This form is archived and cannot accept submissions.',
            ]);
        }
    }
}
