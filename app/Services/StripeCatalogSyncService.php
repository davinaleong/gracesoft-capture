<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class StripeCatalogSyncService
{
    /**
     * @return array{total:int,synced:int,skipped:int}
     */
    public function syncFromStripe(StripeBillingService $stripeBillingService): array
    {
        $prices = $stripeBillingService->listActiveRecurringPricesWithProducts();
        $synced = 0;
        $skipped = 0;

        foreach ($prices as $price) {
            if ($this->syncPrice($price) instanceof Plan) {
                $synced++;

                continue;
            }

            $skipped++;
        }

        return [
            'total' => count($prices),
            'synced' => $synced,
            'skipped' => $skipped,
        ];
    }

    public function syncPrice(array $price): ?Plan
    {
        $priceId = trim((string) Arr::get($price, 'id', ''));
        $product = Arr::get($price, 'product', []);

        $productId = '';

        if (is_string($product)) {
            $productId = trim($product);
            $product = [];
        } elseif (is_array($product)) {
            $productId = trim((string) Arr::get($product, 'id', ''));
        }

        $slug = $this->resolveSlug($price, is_array($product) ? $product : []);
        $plan = $this->resolvePlan($slug, $priceId, $productId);

        if (! $plan) {
            return null;
        }

        $name = trim((string) Arr::get($product, 'name', Arr::get($price, 'nickname', $plan->name)));

        $plan->forceFill([
            'name' => $name !== '' ? $name : $plan->name,
            'stripe_price_id' => $priceId !== '' ? $priceId : $plan->stripe_price_id,
            'stripe_product_id' => $productId !== '' ? $productId : $plan->stripe_product_id,
        ])->save();

        return $plan;
    }

    public function syncProduct(array $product): ?Plan
    {
        $productId = trim((string) Arr::get($product, 'id', ''));
        $slug = $this->resolveSlug([], $product);
        $plan = $this->resolvePlan($slug, '', $productId);

        if (! $plan) {
            return null;
        }

        $name = trim((string) Arr::get($product, 'name', $plan->name));

        $plan->forceFill([
            'name' => $name !== '' ? $name : $plan->name,
            'stripe_product_id' => $productId !== '' ? $productId : $plan->stripe_product_id,
        ])->save();

        return $plan;
    }

    public function clearDeletedPrice(string $priceId): void
    {
        if ($priceId === '') {
            return;
        }

        Plan::query()
            ->where('stripe_price_id', $priceId)
            ->update(['stripe_price_id' => null]);
    }

    public function clearDeletedProduct(string $productId): void
    {
        if ($productId === '') {
            return;
        }

        Plan::query()
            ->where('stripe_product_id', $productId)
            ->update([
                'stripe_product_id' => null,
                'stripe_price_id' => null,
            ]);
    }

    private function resolvePlan(string $slug, string $priceId, string $productId): ?Plan
    {
        if ($priceId !== '') {
            $byPrice = Plan::query()->where('stripe_price_id', $priceId)->first();

            if ($byPrice) {
                return $byPrice;
            }
        }

        if ($productId !== '') {
            $byProduct = Plan::query()->where('stripe_product_id', $productId)->first();

            if ($byProduct) {
                return $byProduct;
            }
        }

        if ($slug === '') {
            $slug = $this->slugFromConfigMap($priceId, $productId);
        }

        if ($slug === '' || ! in_array($slug, $this->allowedSlugs(), true)) {
            return null;
        }

        $plan = Plan::query()->where('slug', $slug)->first();

        if ($plan) {
            return $plan;
        }

        $defaults = $this->defaultsForSlug($slug);

        return Plan::query()->create([
            'id' => (string) Str::uuid(),
            'name' => Str::title($slug),
            'slug' => $slug,
            'stripe_price_id' => $priceId !== '' ? $priceId : null,
            'stripe_product_id' => $productId !== '' ? $productId : null,
            'max_users' => $defaults['max_users'],
            'max_items' => $defaults['max_items'],
            'max_replies' => $defaults['max_replies'],
        ]);
    }

    private function resolveSlug(array $price, array $product): string
    {
        $metadataTier = $this->resolveTierFromMetadata($price, $product);

        if ($metadataTier !== '') {
            return $metadataTier;
        }

        $candidates = [
            Arr::get($price, 'metadata.capture_plan_slug'),
            Arr::get($price, 'metadata.plan_slug'),
            Arr::get($price, 'metadata.capture_slug'),
            Arr::get($product, 'metadata.capture_plan_slug'),
            Arr::get($product, 'metadata.plan_slug'),
            Arr::get($product, 'metadata.capture_slug'),
            Arr::get($price, 'lookup_key'),
            Arr::get($product, 'name'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $slug = Str::of($candidate)->trim()->lower()->replace(' ', '-')->__toString();

            if ($slug === '') {
                continue;
            }

            if (in_array($slug, $this->allowedSlugs(), true)) {
                return $slug;
            }
        }

        return '';
    }

    private function resolveTierFromMetadata(array $price, array $product): string
    {
        $priceApp = Str::of((string) Arr::get($price, 'metadata.app', ''))->trim()->lower()->__toString();
        $productApp = Str::of((string) Arr::get($product, 'metadata.app', ''))->trim()->lower()->__toString();

        if ($priceApp !== 'capture' && $productApp !== 'capture') {
            return '';
        }

        $tierCandidates = [
            Arr::get($price, 'metadata.tier'),
            Arr::get($product, 'metadata.tier'),
        ];

        foreach ($tierCandidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $slug = Str::of($candidate)->trim()->lower()->replace(' ', '-')->__toString();

            if ($slug !== '' && in_array($slug, $this->allowedSlugs(), true)) {
                return $slug;
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function allowedSlugs(): array
    {
        $value = config('services.stripe.catalog_allowed_slugs', ['growth', 'pro']);

        if (! is_array($value)) {
            return ['growth', 'pro'];
        }

        return array_values(array_filter(array_map(static fn (mixed $item): string => Str::of((string) $item)->trim()->lower()->__toString(), $value)));
    }

    private function slugFromConfigMap(string $priceId, string $productId): string
    {
        $map = config('services.stripe.plan_map', []);

        if (! is_array($map)) {
            return '';
        }

        foreach ($map as $slug => $ids) {
            if (! is_string($slug) || ! is_array($ids)) {
                continue;
            }

            $mapPrice = trim((string) Arr::get($ids, 'price_id', ''));
            $mapProduct = trim((string) Arr::get($ids, 'product_id', ''));

            if ($priceId !== '' && $mapPrice !== '' && hash_equals($mapPrice, $priceId)) {
                return Str::lower($slug);
            }

            if ($productId !== '' && $mapProduct !== '' && hash_equals($mapProduct, $productId)) {
                return Str::lower($slug);
            }
        }

        return '';
    }

    /**
     * @return array{max_users:int,max_items:int,max_replies:int}
     */
    private function defaultsForSlug(string $slug): array
    {
        return match ($slug) {
            'pro' => ['max_users' => 20, 'max_items' => 5000, 'max_replies' => 20000],
            default => ['max_users' => 5, 'max_items' => 500, 'max_replies' => 2000],
        };
    }
}
