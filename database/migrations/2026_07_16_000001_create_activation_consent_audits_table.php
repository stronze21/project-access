<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activation_consent_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('attempt_id')->unique();
            $table->string('resident_identifier')->index();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);
            $table->string('terms_version');
            $table->string('privacy_version');
            $table->string('bhwis_consent_version');
            // DATETIME avoids MySQL/MariaDB's legacy implicit defaults for
            // consecutive non-null TIMESTAMP columns. These values are always
            // supplied explicitly when the consent audit is created.
            $table->dateTime('terms_accepted_at');
            $table->dateTime('privacy_acknowledged_at');
            $table->dateTime('bhwis_consented_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_name')->nullable();
            $table->string('outcome')->default('started')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_consent_audits');
    }
};
