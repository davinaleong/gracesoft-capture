<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeBillingService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActiveRecurringPricesWithProducts(): array
    {
        $prices = [];
        $startingAfter = null;

        do {
            $query = [
                'active' => 'true',
                'type' => 'recurring',
                'limit' => 100,
                'expand[]' => 'data.product',
            ];

            if (is_string($startingAfter) && $startingAfter !== '') {
                $query['starting_after'] = $startingAfter;
            }

            $response = $this->request()->get('/v1/prices', $query);

            if (! $response->successful()) {
                throw new RuntimeException('Unable to fetch Stripe prices catalog.');
            }

            $batch = $response->json('data', []);

            foreach ($batch as $item) {
                if (is_array($item)) {
                    $prices[] = $item;
                }
            }

            $startingAfter = (string) data_get($batch, (count($batch) - 1) . '.id', '');
            $hasMore = (bool) $response->json('has_more', false);
        } while ($hasMore && $startingAfter !== '');

        return $prices;
    }

    public function ensureCustomer(Account $account): string
    {
        if (is_string($account->stripe_customer_id) && $account->stripe_customer_id !== '') {
            return $account->stripe_customer_id;
        }

        $response = $this->request()->post('/v1/customers', [
            'name' => $account->name,
            'metadata[account_id]' => $account->id,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to create Stripe customer.');
        }

        $customerId = (string) $response->json('id', '');

        if ($customerId === '') {
            throw new RuntimeException('Stripe customer response did not include an id.');
        }

        $account->forceFill(['stripe_customer_id' => $customerId])->save();

        return $customerId;
    }

    public function createCheckoutSession(Account $account, string $priceId): string
    {
        $customerId = $this->ensureCustomer($account);

        $response = $this->request()
            ->withHeaders(['Idempotency-Key' => 'checkout:' . $account->id . ':' . $priceId . ':' . now()->format('YmdHi')])
            ->post('/v1/checkout/sessions', [
                'mode' => 'subscription',
                'customer' => $customerId,
                'success_url' => (string) config('services.stripe.checkout_success_url'),
                'cancel_url' => (string) config('services.stripe.checkout_cancel_url'),
                'line_items[0][price]' => $priceId,
                'line_items[0][quantity]' => 1,
                'allow_promotion_codes' => 'true',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to create Stripe checkout session.');
        }

        $url = (string) $response->json('url', '');

        if ($url === '') {
            throw new RuntimeException('Stripe checkout response did not include a redirect URL.');
        }

        return $url;
    }

    public function createPortalSession(Account $account): string
    {
        $customerId = $this->ensureCustomer($account);

        $response = $this->request()
            ->withHeaders(['Idempotency-Key' => 'portal:' . $account->id . ':' . now()->format('YmdHi')])
            ->post('/v1/billing_portal/sessions', [
                'customer' => $customerId,
                'return_url' => (string) config('services.stripe.portal_return_url'),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to create Stripe billing portal session.');
        }

        $url = (string) $response->json('url', '');

        if ($url === '') {
            throw new RuntimeException('Stripe billing portal response did not include a redirect URL.');
        }

        return $url;
    }

    private function request(): PendingRequest
    {
        $secret = (string) config('services.stripe.secret', '');

        if ($secret === '') {
            throw new RuntimeException('Stripe secret is not configured.');
        }

        $baseUrl = rtrim((string) config('services.stripe.api_base_url', 'https://api.stripe.com'), '/');

        return Http::asForm()
            ->acceptJson()
            ->withToken($secret)
            ->baseUrl($baseUrl)
            ->timeout((int) config('capture.features.stripe_timeout_seconds', 10));
    }
}
