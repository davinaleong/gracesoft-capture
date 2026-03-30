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
        Schema::create('break_glass_approvals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('account_id')->nullable()->index();
            $table->string('scope')->index();
            $table->uuid('requested_by_administrator_uuid')->index();
            $table->uuid('approved_by_administrator_uuid')->nullable()->index();
            $table->text('reason');
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'scope', 'approved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('break_glass_approvals');
    }
};
