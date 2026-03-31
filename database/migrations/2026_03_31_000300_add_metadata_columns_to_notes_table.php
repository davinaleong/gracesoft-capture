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
        Schema::table('notes', function (Blueprint $table) {
            $table->string('visibility', 20)->default('internal')->after('content');
            $table->boolean('is_pinned')->default(false)->after('visibility');
            $table->json('tags')->nullable()->after('is_pinned');
            $table->timestamp('reminder_at')->nullable()->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'is_pinned', 'tags', 'reminder_at']);
        });
    }
};
