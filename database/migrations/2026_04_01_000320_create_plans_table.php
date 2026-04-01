<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 80);
            $table->string('slug', 40)->unique();
            $table->string('stripe_price_id')->nullable()->unique();
            $table->string('stripe_product_id')->nullable();
            $table->unsignedInteger('max_users')->default(1);
            $table->unsignedInteger('max_items')->nullable();
            $table->unsignedInteger('max_replies')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('plans')->insert([
            [
                'id' => (string) Str::uuid(),
                'name' => 'Free',
                'slug' => 'free',
                'stripe_price_id' => null,
                'stripe_product_id' => null,
                'max_users' => 1,
                'max_items' => 50,
                'max_replies' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Growth',
                'slug' => 'growth',
                'stripe_price_id' => env('STRIPE_GROWTH_PRICE_ID'),
                'stripe_product_id' => env('STRIPE_GROWTH_PRODUCT_ID'),
                'max_users' => 5,
                'max_items' => 500,
                'max_replies' => 2000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Pro',
                'slug' => 'pro',
                'stripe_price_id' => env('STRIPE_PRO_PRICE_ID'),
                'stripe_product_id' => env('STRIPE_PRO_PRODUCT_ID'),
                'max_users' => 20,
                'max_items' => 5000,
                'max_replies' => 20000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
