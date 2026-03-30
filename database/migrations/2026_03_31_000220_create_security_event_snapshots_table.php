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
        Schema::create('security_event_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->date('snapshot_date')->index();
            $table->string('metric_key')->index();
            $table->unsignedBigInteger('metric_value')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['snapshot_date', 'metric_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_event_snapshots');
    }
};
