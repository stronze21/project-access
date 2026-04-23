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
        Schema::create('ayuda_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['cash', 'goods', 'services', 'mixed'])->default('cash');
            $table->decimal('amount', 10, 2)->nullable();
            $table->text('goods_description')->nullable();
            $table->text('services_description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('frequency', ['one-time', 'weekly', 'monthly', 'quarterly', 'annual'])->default('one-time');
            $table->integer('distribution_count')->default(1);
            $table->decimal('total_budget', 14, 2)->nullable();
            $table->decimal('budget_used', 14, 2)->default(0.00);
            $table->integer('max_beneficiaries')->nullable();
            $table->integer('current_beneficiaries')->default(0);
            $table->boolean('requires_verification')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ayuda_programs');
    }
};