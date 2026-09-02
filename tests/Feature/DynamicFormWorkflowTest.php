<?php

namespace Tests\Feature;

use App\Livewire\Admin\DynamicFormList;
use App\Livewire\Admin\DynamicFormSubmissionShow;
use App\Models\DynamicForm;
use App\Models\User;
use App\Services\DynamicForm\FormSchemaService;
use App\Services\DynamicForm\FormSubmissionService;
use App\Services\DynamicForm\FormWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DynamicFormWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_schema_save_rejects_missing_initial_status(): void
    {
        $form = $this->blankForm();
        $schema = app(FormSchemaService::class);

        try {
            $schema->save($form->fresh(), [
                'title' => 'Intake',
                'fields' => [
                    ['label' => 'Name', 'type' => 'short_text'],
                ],
                'statuses' => collect($this->statusPayload($form))->map(function (array $status) {
                    $status['is_initial'] = false;

                    return $status;
                })->all(),
                'transitions' => $this->transitionPayload($form),
            ]);
            $this->fail('Expected missing initial status to fail validation.');
        } catch (ValidationException $exception) {
            $this->assertTrue($exception->validator->errors()->has('statuses'));
        }
    }

    public function test_slug_and_field_keys_are_generated_and_immutable(): void
    {
        $form = $this->blankForm();
        $originalSlug = $form->slug;
        $schema = app(FormSchemaService::class);
        $form->load(['statuses', 'transitions']);

        $form = $schema->save($form, [
            'title' => 'Household intake',
            'slug' => 'hacker-slug',
            'fields' => [
                ['label' => 'Full name', 'type' => 'short_text', 'is_required' => true],
                ['label' => 'Full name', 'type' => 'short_text'],
            ],
            'statuses' => $this->statusPayload($form),
            'transitions' => $this->transitionPayload($form),
        ]);

        $this->assertSame($originalSlug, $form->slug);
        $this->assertSame('untitled-form', $originalSlug);
        $keys = $form->fields()->orderBy('sort_order')->pluck('key')->all();
        $this->assertSame(['full_name', 'full_name_2'], $keys);

        $field = $form->fields()->orderBy('sort_order')->first();

        $form = $schema->save($form->fresh(['fields', 'statuses', 'transitions']), [
            'title' => 'Renamed form',
            'slug' => 'another-hack',
            'fields' => [[
                'id' => $field->id,
                'key' => 'hacked_key',
                'label' => 'Display name',
                'type' => 'short_text',
                'is_required' => true,
            ]],
            'statuses' => $this->statusPayload($form),
            'transitions' => $this->transitionPayload($form),
        ]);

        $this->assertSame($originalSlug, $form->slug);
        $this->assertSame('full_name', $form->fields()->first()->key);
        $this->assertSame('Display name', $form->fields()->first()->label);
    }

    public function test_submit_validates_required_type_and_stores_json_plus_projection_rows(): void
    {
        $form = $this->publishedForm();
        $staff = $this->staff(['fill-forms', 'view-forms']);

        try {
            app(FormSubmissionService::class)->create($form, ['full_name' => '', 'age' => 'not-a-number'], [], $staff);
            $this->fail('Expected invalid answers to fail validation.');
        } catch (ValidationException $exception) {
            $this->assertTrue($exception->validator->errors()->has('answers.full_name'));
            $this->assertTrue($exception->validator->errors()->has('answers.age'));
        }

        $submission = app(FormSubmissionService::class)->create(
            $form,
            ['full_name' => 'Juan Dela Cruz', 'age' => 34],
            [],
            $staff,
        );

        $this->assertSame('Juan Dela Cruz', $submission->answers['full_name']);
        $this->assertEquals(34, $submission->answers['age']);
        $this->assertNotEmpty($submission->schema_snapshot);
        $this->assertDatabaseHas('dynamic_form_submission_values', [
            'dynamic_form_submission_id' => $submission->id,
            'field_key' => 'full_name',
            'value_string' => 'Juan Dela Cruz',
        ]);
        $this->assertDatabaseHas('dynamic_form_submission_values', [
            'dynamic_form_submission_id' => $submission->id,
            'field_key' => 'age',
            'value_number' => 34,
        ]);
    }

    public function test_illegal_status_transition_is_rejected_and_legal_one_writes_an_event(): void
    {
        $form = $this->publishedForm();
        $staff = $this->staff(['fill-forms', 'process-forms']);
        $submission = app(FormSubmissionService::class)->create(
            $form,
            ['full_name' => 'Ana Santos', 'age' => 22],
            [],
            $staff,
        );

        $done = $form->statuses()->where('key', 'done')->firstOrFail();
        $submitted = $form->statuses()->where('key', 'submitted')->firstOrFail();
        $workflow = app(FormWorkflowService::class);

        try {
            $workflow->transition($submission, $done->id, $staff, 'skip ahead');
            $this->fail('Expected an illegal transition to fail.');
        } catch (ValidationException $exception) {
            $this->assertTrue($exception->validator->errors()->has('status'));
        }

        $updated = $workflow->transition($submission->fresh(), $submitted->id, $staff, 'Ready for review');

        $this->assertSame('submitted', $updated->status->key);
        $this->assertDatabaseHas('dynamic_form_submission_events', [
            'dynamic_form_submission_id' => $updated->id,
            'to_status_id' => $submitted->id,
            'note' => 'Ready for review',
        ]);
    }

    public function test_filler_cannot_open_the_builder_and_processor_can_move_status(): void
    {
        $form = $this->publishedForm();
        $filler = $this->staff(['fill-forms']);
        $processor = $this->staff(['fill-forms', 'process-forms', 'view-forms']);

        $this->actingAs($filler)->get(route('forms.edit', $form))->assertForbidden();

        $submission = app(FormSubmissionService::class)->create(
            $form,
            ['full_name' => 'Pedro Reyes', 'age' => 40],
            [],
            $filler,
        );

        $submitted = $form->statuses()->where('key', 'submitted')->firstOrFail();

        Livewire::actingAs($processor)
            ->test(DynamicFormSubmissionShow::class, ['submission' => $submission])
            ->set('nextStatusId', $submitted->id)
            ->set('note', 'Queued')
            ->call('transition')
            ->assertHasNoErrors();

        $this->assertSame('submitted', $submission->fresh()->status->key);

        Livewire::actingAs($filler)
            ->test(DynamicFormSubmissionShow::class, ['submission' => $submission->fresh()])
            ->set('nextStatusId', $form->statuses()->where('key', 'in_progress')->value('id'))
            ->call('transition')
            ->assertForbidden();
    }

    public function test_csv_export_includes_filterable_columns(): void
    {
        $form = $this->publishedForm();
        $staff = $this->staff(['fill-forms', 'view-forms']);
        app(FormSubmissionService::class)->create(
            $form,
            ['full_name' => 'Maria Cruz', 'age' => 29],
            [],
            $staff,
        );

        $response = $this->actingAs($staff)->get(route('forms.export', $form));
        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Full name [filterable]', $csv);
        $this->assertStringContainsString('Age [filterable]', $csv);
        $this->assertStringContainsString('Maria Cruz', $csv);
        $this->assertStringContainsString('29', $csv);
    }

    public function test_staff_can_create_a_form_from_the_list_and_fill_it(): void
    {
        $manager = $this->staff(['manage-forms', 'fill-forms', 'view-forms']);

        Livewire::actingAs($manager)
            ->test(DynamicFormList::class)
            ->set('newTitle', 'Barangay intake')
            ->call('createForm')
            ->assertRedirect();

        $form = DynamicForm::query()->where('title', 'Barangay intake')->firstOrFail();
        $this->assertGreaterThanOrEqual(5, $form->statuses()->count());

        $schema = app(FormSchemaService::class);
        $form->load(['statuses', 'transitions']);
        $form = $schema->save($form, [
            'title' => $form->title,
            'fields' => [[
                'key' => 'notes',
                'label' => 'Notes',
                'type' => 'short_text',
                'is_required' => true,
                'is_filterable' => true,
                'is_active' => true,
            ]],
            'statuses' => $this->statusPayload($form),
            'transitions' => $this->transitionPayload($form),
        ]);
        $schema->publish($form);
        $form = $form->fresh();

        $this->actingAs($manager)
            ->get(route('forms.fill', ['form' => $form->id]))
            ->assertOk()
            ->assertSee('Barangay intake')
            ->assertSee('Save draft');

        $submission = app(FormSubmissionService::class)->create(
            $form->fresh(['fields', 'statuses']),
            ['notes' => 'Walk-in applicant'],
            [],
            $manager,
            null,
            true,
        );
        $this->assertSame('Walk-in applicant', $submission->answers['notes']);
        $this->assertSame('submitted', $submission->status->key);
        $this->assertDatabaseHas('dynamic_form_submission_values', [
            'dynamic_form_submission_id' => $submission->id,
            'field_key' => 'notes',
            'value_string' => 'Walk-in applicant',
        ]);
    }

    private function blankForm(): DynamicForm
    {
        return app(FormSchemaService::class)->createBlank('Untitled form', $this->staff(['manage-forms'])->id);
    }

    private function publishedForm(): DynamicForm
    {
        $schema = app(FormSchemaService::class);
        $form = $this->blankForm();
        $form->load(['statuses', 'transitions']);

        $form = $schema->save($form, [
            'title' => 'Household intake',
            'fields' => [
                [
                    'key' => 'full_name',
                    'label' => 'Full name',
                    'type' => 'short_text',
                    'is_required' => true,
                    'is_filterable' => true,
                    'is_active' => true,
                ],
                [
                    'key' => 'age',
                    'label' => 'Age',
                    'type' => 'number',
                    'is_required' => true,
                    'is_filterable' => true,
                    'is_active' => true,
                ],
            ],
            'statuses' => $this->statusPayload($form),
            'transitions' => $this->transitionPayload($form),
            'tags' => [
                ['label' => 'Urgent', 'color' => 'red'],
            ],
        ]);

        return $schema->publish($form);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function statusPayload(DynamicForm $form): array
    {
        return $form->statuses()->orderBy('sort_order')->get()->map(fn ($status) => [
            'id' => $status->id,
            'temp_id' => (string) $status->id,
            'key' => $status->key,
            'label' => $status->label,
            'color' => $status->color,
            'is_initial' => $status->is_initial,
            'is_terminal' => $status->is_terminal,
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transitionPayload(DynamicForm $form): array
    {
        return $form->transitions()->get()->map(fn ($transition) => [
            'from_temp_id' => (string) $transition->from_status_id,
            'to_temp_id' => (string) $transition->to_status_id,
        ])->all();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function staff(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }
}
