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
        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('enquiry_id')->constrained()->cascadeOnDelete();
            $table->uuid('account_id')->index();
            $table->enum('sender_type', ['user', 'administrator', 'external', 'system']);
            $table->uuid('sender_id')->nullable();
            $table->string('email')->nullable();
            $table->text('content');
            $table->boolean('is_internal')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('enquiry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replies');
    }
};
