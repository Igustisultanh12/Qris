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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 50)->nullable();
            $table->string('action', 100);
            $table->string('entity', 100);
            $table->string('entity_id', 64)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 64)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['entity', 'entity_id']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('ticket_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->string('category', 50)->default('general');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'pending', 'answered', 'resolved', 'closed'])->default('open');
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('ticket_number');
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_admin_reply')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('type', ['fixed', 'percentage'])->default('fixed');
            $table->unsignedBigInteger('value'); // fixed amount in IDR or percentage (e.g. 10 for 10%)
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->unsignedBigInteger('min_spend')->default(0);
            $table->integer('max_uses')->nullable();
            $table->integer('uses_count')->default(0);
            $table->integer('max_uses_per_customer')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->timestamps();

            $table->index(['coupon_id', 'customer_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general');
            $table->string('key', 100)->unique();
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string'); // string, integer, boolean, json, decimal
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('group');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('audit_logs');
    }
};
