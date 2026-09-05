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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('transaction_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->index();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('fee')->default(0);
            $table->unsignedBigInteger('total');
            $table->enum('fee_mode', ['absorbed', 'charged_to_customer'])->default('charged_to_customer');
            $table->text('qris_static');
            $table->text('qris_dynamic');
            $table->string('qr_image_path')->nullable();
            $table->enum('status', ['pending', 'generated', 'paid', 'expired', 'cancelled', 'failed'])->default('generated');
            $table->enum('source', ['web', 'api', 'pos'])->default('api');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('idempotency_key')->nullable()->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index(['merchant_id', 'created_at']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('transaction_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->enum('fee_type', ['none', 'fixed', 'percentage'])->default('none');
            $table->decimal('fee_rate', 8, 4)->default(0);
            $table->unsignedBigInteger('fee_amount')->default(0);
            $table->enum('fee_mode', ['absorbed', 'charged_to_customer'])->default('charged_to_customer');
            $table->unsignedBigInteger('platform_cut')->default(0);
            $table->unsignedBigInteger('merchant_net')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_fees');
        Schema::dropIfExists('transactions');
    }
};
