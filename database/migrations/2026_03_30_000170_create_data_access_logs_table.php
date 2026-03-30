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
        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');
            $table->uuid('actor_id')->nullable();
            $table->string('actor_source_table');
            $table->uuid('account_id')->nullable()->index();
            $table->string('target_type');
            $table->string('target_id')->nullable();
            $table->string('access_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_type', 'actor_id']);
            $table->index('target_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_access_logs');
    }
};
