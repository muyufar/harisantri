<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('activity_name');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('venue');
            $table->string('organizer');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('event_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'key']);
        });

        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('business_name');
            $table->string('whatsapp', 20)->index();
            $table->string('email')->nullable();
            $table->string('address');
            $table->string('district');
            $table->string('regency');
            $table->string('product_type');
            $table->text('product_description');
            $table->string('logo_path')->nullable();
            $table->text('special_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('booth_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('booth_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('type');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['event_id', 'code']);
        });

        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('size');
            $table->unsignedInteger('place_price');
            $table->unsignedInteger('tent_price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['event_id', 'size']);
        });

        Schema::create('booths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('booth_zones')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('booth_categories')->nullOnDelete();
            $table->string('code');
            $table->string('type');
            $table->string('size')->nullable();
            $table->string('state')->default('available');
            $table->decimal('pos_x', 6, 2)->default(0);
            $table->decimal('pos_y', 6, 2)->default(0);
            $table->decimal('width', 6, 2)->default(6);
            $table->decimal('height', 6, 2)->default(8);
            $table->timestamps();
            $table->unique(['event_id', 'code']);
            $table->index(['event_id', 'type', 'state']);
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('type')->default('umkm');
            $table->string('status')->index();
            $table->string('whatsapp', 20)->index();
            $table->dateTime('hold_expires_at')->nullable()->index();
            $table->dateTime('terms_accepted_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('tent_total')->default(0);
            $table->unsignedInteger('grand_total')->default(0);
            $table->text('cancellation_note')->nullable();
            $table->timestamps();
        });

        Schema::create('registration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booth_id')->constrained()->restrictOnDelete();
            $table->string('booth_code');
            $table->string('zone_name');
            $table->string('size')->nullable();
            $table->boolean('with_tent')->default(false);
            $table->unsignedInteger('place_price');
            $table->unsignedInteger('tent_price');
            $table->unsignedInteger('line_total');
            $table->timestamps();
            $table->index('booth_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('method')->default('qris');
            $table->string('gateway')->default('manual');
            $table->string('status')->index();
            $table->string('proof_path')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('decision');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('sponsorship_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sponsorship_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsorship_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('booth_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('company_name');
            $table->string('pic_name');
            $table->string('whatsapp', 20)->index();
            $table->string('business_type');
            $table->string('area_requirement');
            $table->text('facility_needs')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->index();
            $table->unsignedInteger('agreed_price')->nullable();
            $table->timestamps();
        });

        Schema::create('booth_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booth_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('registration_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('sponsorship_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sponsorship_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsorship_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('location_note')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('booth_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->dateTime('checked_in_at');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('phone_locks', function (Blueprint $table) {
            $table->string('phone', 20)->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_locks');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('booth_checkins');
        Schema::dropIfExists('sponsorship_offers');
        Schema::dropIfExists('booth_reservations');
        Schema::dropIfExists('sponsorship_requests');
        Schema::dropIfExists('sponsorship_categories');
        Schema::dropIfExists('payment_verifications');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('registration_items');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('booths');
        Schema::dropIfExists('price_rules');
        Schema::dropIfExists('booth_zones');
        Schema::dropIfExists('booth_categories');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('event_settings');
        Schema::dropIfExists('events');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'is_active']);
        });
    }
};
