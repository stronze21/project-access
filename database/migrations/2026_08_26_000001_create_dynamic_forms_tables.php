<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('link_to_resident')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dynamic_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_form_id')->constrained('dynamic_forms')->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('type', 50);
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('options')->nullable();
            $table->timestamps();

            $table->unique(['dynamic_form_id', 'key']);
            $table->index(['dynamic_form_id', 'sort_order']);
        });

        Schema::create('dynamic_form_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_form_id')->constrained('dynamic_forms')->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('color', 50)->default('slate');
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['dynamic_form_id', 'key']);
        });

        Schema::create('dynamic_form_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_form_id')->constrained('dynamic_forms')->cascadeOnDelete();
            $table->foreignId('from_status_id')->constrained('dynamic_form_statuses')->cascadeOnDelete();
            $table->foreignId('to_status_id')->constrained('dynamic_form_statuses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['from_status_id', 'to_status_id'], 'dynamic_form_status_transition_unique');
        });

        Schema::create('dynamic_form_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_form_id')->constrained('dynamic_forms')->cascadeOnDelete();
            $table->string('label');
            $table->string('color', 50)->default('blue');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('dynamic_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_form_id')->constrained('dynamic_forms')->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->foreignId('status_id')->constrained('dynamic_form_statuses')->restrictOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained('residents')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('schema_snapshot')->nullable();
            $table->json('answers')->nullable();
            $table->timestamps();

            $table->index(['dynamic_form_id', 'status_id']);
            $table->index(['created_by', 'dynamic_form_id']);
            $table->index('created_at');
        });

        Schema::create('dynamic_form_submission_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dynamic_form_submission_id');
            $table->string('field_key');
            $table->string('value_string', 255)->nullable();
            $table->decimal('value_number', 15, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->timestamps();

            $table->foreign('dynamic_form_submission_id', 'dfs_values_submission_fk')
                ->references('id')
                ->on('dynamic_form_submissions')
                ->cascadeOnDelete();
            $table->index(['dynamic_form_submission_id', 'field_key'], 'dfs_values_submission_field_index');
            $table->index(['field_key', 'value_string'], 'dfs_values_key_string_index');
            $table->index(['field_key', 'value_number'], 'dfs_values_key_number_index');
            $table->index(['field_key', 'value_date'], 'dfs_values_key_date_index');
        });

        Schema::create('dynamic_form_submission_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dynamic_form_submission_id');
            $table->foreignId('from_status_id')->nullable()->constrained('dynamic_form_statuses')->nullOnDelete();
            $table->foreignId('to_status_id')->nullable()->constrained('dynamic_form_statuses')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('dynamic_form_submission_id', 'dfs_events_submission_fk')
                ->references('id')
                ->on('dynamic_form_submissions')
                ->cascadeOnDelete();
            $table->index('dynamic_form_submission_id', 'dfs_events_submission_index');
        });

        Schema::create('dynamic_form_submission_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dynamic_form_submission_id');
            $table->unsignedBigInteger('dynamic_form_tag_id');
            $table->timestamps();

            $table->foreign('dynamic_form_submission_id', 'dfs_tag_submission_fk')
                ->references('id')
                ->on('dynamic_form_submissions')
                ->cascadeOnDelete();
            $table->foreign('dynamic_form_tag_id', 'dfs_tag_tag_fk')
                ->references('id')
                ->on('dynamic_form_tags')
                ->cascadeOnDelete();
            $table->unique(['dynamic_form_submission_id', 'dynamic_form_tag_id'], 'dfs_submission_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_form_submission_tag');
        Schema::dropIfExists('dynamic_form_submission_events');
        Schema::dropIfExists('dynamic_form_submission_values');
        Schema::dropIfExists('dynamic_form_submissions');
        Schema::dropIfExists('dynamic_form_tags');
        Schema::dropIfExists('dynamic_form_status_transitions');
        Schema::dropIfExists('dynamic_form_statuses');
        Schema::dropIfExists('dynamic_form_fields');
        Schema::dropIfExists('dynamic_forms');
    }
};
