<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('credential_uuid')->unique();
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('token_version')->default(1);
            $table->timestamp('valid_until')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('user_zone_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('zone');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'zone']);
        });

        Schema::create('visitor_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('tracking_code')->unique();
            $table->string('name');
            $table->string('contact_number', 40);
            $table->string('id_type');
            $table->string('id_number');
            $table->string('purpose');
            $table->string('destination');
            $table->date('visit_date')->index();
            $table->string('status')->default('pending')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('gate_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('pass_uuid')->unique();
            $table->string('status')->default('active')->index();
            $table->string('allowed_gate')->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('expires_at')->index();
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('exited_at')->nullable();
            $table->timestamps();
        });

        Schema::create('access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visitor_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gate_pass_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('credential_type');
            $table->string('zone')->index();
            $table->string('direction')->nullable();
            $table->string('location')->nullable();
            $table->string('result')->index();
            $table->string('anomaly')->nullable();
            $table->timestamp('scanned_at')->index();
            $table->timestamps();
            $table->index(['zone', 'scanned_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('access_events');
        Schema::dropIfExists('gate_passes');
        Schema::dropIfExists('visitor_registrations');
        Schema::dropIfExists('user_zone_authorizations');
        Schema::dropIfExists('digital_ids');
    }
};
