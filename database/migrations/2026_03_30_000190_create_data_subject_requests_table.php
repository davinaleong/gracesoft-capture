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
        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('account_id')->nullable()->index();
            $table->uuid('subject_user_id')->nullable()->index();
            $table->string('subject_email')->nullable()->index();
            $table->enum('request_type', ['export', 'delete', 'restrict']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('resolved_by_administrator_uuid')->nullable();
            $table->json('resolution_metadata')->nullable();
            $table->timestamps();

            $table->index(['request_type', 'status']);
            $table->index('requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_subject_requests');
    }
};
