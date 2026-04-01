<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use App\Models\Plan;

uses(RefreshDatabase::class);

test('mail health check command dispatches test email', function () {
    Mail::shouldReceive('raw')->once();
    config()->set('mail.from.address', 'ops@example.com');

    Artisan::call('capture:mail:health-check');
});

test('secrets rotation command reports within policy window', function () {
    config([
        'capture.features.secret_rotation_interval_days' => 90,
        'capture.features.last_secret_rotation_at' => now()->subDays(10)->toIso8601String(),
    ]);

    Artisan::call('capture:secrets:rotation:check');

    expect(Artisan::output())->toContain('within policy window');
});

test('stripe catalog sync command updates local plan mappings', function () {
    config()->set('services.stripe.secret', 'sk_test_123');

    Http::fake([
        'https://api.stripe.com/v1/prices*' => Http::response([
            'object' => 'list',
            'has_more' => false,
            'data' => [
                [
                    'id' => 'price_growth_sync_123',
                    'object' => 'price',
                    'lookup_key' => 'growth',
                    'product' => [
                        'id' => 'prod_growth_sync_123',
                        'object' => 'product',
                        'name' => 'Growth',
                        'metadata' => [
                            'capture_plan_slug' => 'growth',
                        ],
                    ],
                ],
                [
                    'id' => 'price_pro_sync_123',
                    'object' => 'price',
                    'lookup_key' => 'pro',
                    'product' => [
                        'id' => 'prod_pro_sync_123',
                        'object' => 'product',
                        'name' => 'Pro',
                        'metadata' => [
                            'capture_plan_slug' => 'pro',
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    Artisan::call('capture:stripe:catalog:sync');

    $growth = Plan::query()->where('slug', 'growth')->firstOrFail();
    $pro = Plan::query()->where('slug', 'pro')->firstOrFail();

    expect($growth->stripe_price_id)->toBe('price_growth_sync_123');
    expect($growth->stripe_product_id)->toBe('prod_growth_sync_123');
    expect($pro->stripe_price_id)->toBe('price_pro_sync_123');
    expect($pro->stripe_product_id)->toBe('prod_pro_sync_123');
    expect(Artisan::output())->toContain('Stripe catalog sync completed');
});
