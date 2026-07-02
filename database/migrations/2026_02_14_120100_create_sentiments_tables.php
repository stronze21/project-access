<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sentiment_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('media_kind', 16)->default('none');
            $table->string('media_disk', 32)->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_mime_type', 120)->nullable();
            $table->string('media_original_name')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_comments_locked')->default(false);
            $table->unsignedInteger('reports_count')->default(0);
            $table->timestamp('hidden_at')->nullable();
            $table->string('hidden_reason', 120)->nullable();
            $table->boolean('is_permanently_deleted')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_pinned', 'created_at']);
            $table->index(['reports_count', 'hidden_at']);
        });

        Schema::create('sentiment_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('sentiment_posts')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('sentiment_comments')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->unsignedInteger('reports_count')->default(0);
            $table->timestamp('hidden_at')->nullable();
            $table->string('hidden_reason', 120)->nullable();
            $table->boolean('is_permanently_deleted')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['post_id', 'parent_id', 'created_at']);
            $table->index(['reports_count', 'hidden_at']);
        });

        Schema::create('sentiment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('reactionable');
            $table->string('reaction', 16);
            $table->timestamps();

            $table->unique(['user_id', 'reactionable_type', 'reactionable_id'], 'sentiment_reactions_unique_user_target');
            $table->index(['reaction', 'created_at']);
        });

        Schema::create('sentiment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('reportable');
            $table->string('status', 32)->default('open');
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['reporter_user_id', 'reportable_type', 'reportable_id'], 'sentiment_reports_unique_reporter_target');
            $table->index(['status', 'created_at']);
        });

        Schema::create('sentiment_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('followed_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_user_id', 'followed_user_id'], 'sentiment_follows_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentiment_follows');
        Schema::dropIfExists('sentiment_reports');
        Schema::dropIfExists('sentiment_reactions');
        Schema::dropIfExists('sentiment_comments');
        Schema::dropIfExists('sentiment_posts');
    }
};

