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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('merchant_code')->unique();
            $table->string('name');
            $table->string('store_name')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('mcc', 10)->nullable();
            $table->string('acquirer_name')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('fee_mode', ['absorbed', 'charged_to_customer'])->default('charged_to_customer');
            $table->enum('custom_fee_type', ['none', 'fixed', 'percentage'])->default('none');
            $table->decimal('custom_fee_value', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('merchant_code');
        });

        Schema::create('merchant_qris', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->text('qris_static');
            $table->string('qris_version', 10)->default('01');
            $table->string('merchant_name_qris')->nullable();
            $table->string('merchant_city_qris')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('currency', 10)->default('360');
            $table->string('mcc', 10)->nullable();
            $table->string('nmid')->nullable();
            $table->string('acquirer')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'is_primary']);
            $table->index('customer_id');
        });

        Schema::create('qris_payloads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('merchant_qris_id')->nullable()->constrained('merchant_qris')->nullOnDelete();
            $table->text('raw_payload');
            $table->json('parsed_data')->nullable();
            $table->string('crc', 10)->nullable();
            $table->boolean('is_valid')->default(true);
            $table->json('validation_errors')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qris_payloads');
        Schema::dropIfExists('merchant_qris');
        Schema::dropIfExists('merchants');
    }
};
