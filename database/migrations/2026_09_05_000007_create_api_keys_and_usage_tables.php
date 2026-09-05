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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key_prefix', 16);
            $table->string('key_hash', 64)->unique();
            $table->string('secret_hash', 64)->nullable();
            $table->text('ip_whitelist')->nullable(); // comma-separated or json
            $table->integer('rate_limit_per_minute')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'is_active']);
            $table->index('key_hash');
        });

        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 64)->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('response_status');
            $table->json('response_body')->nullable();
            $table->integer('duration_ms')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['customer_id', 'created_at']);
            $table->index(['api_key_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
        Schema::dropIfExists('api_keys');
    }
};
