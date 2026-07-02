<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('complaint_comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_comment_id')->constrained('complaint_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction', 16);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['complaint_comment_id', 'user_id']);
            $table->index(['complaint_comment_id', 'reaction'], 'comment_reaction_comment_reaction_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('complaint_comment_reactions');
    }
};
