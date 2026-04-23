<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->nullable()->constrained()->onDelete('set null');
            $table->string('resident_id')->unique();
            $table->string('qr_code')->unique()->nullable();
            $table->string('rfid_number')->unique()->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('civil_status', ['single', 'married', 'widowed', 'divorced', 'separated', 'other'])->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('id_card_path')->nullable();
            $table->enum('relationship_to_head', [
                'head', 'spouse', 'child', 'sibling', 'parent',
                'grandchild', 'grandparent', 'in-law', 'other_relative',
                'non-relative', 'domestic_worker', 'boarder'
            ])->nullable();
            $table->string('occupation')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('educational_attainment')->nullable();
            $table->boolean('is_registered_voter')->default(false);
            $table->boolean('is_pwd')->default(false);
            $table->boolean('is_senior_citizen')->default(false);
            $table->boolean('is_solo_parent')->default(false);
            $table->boolean('is_pregnant')->default(false);
            $table->boolean('is_lactating')->default(false);
            $table->boolean('is_indigenous')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};