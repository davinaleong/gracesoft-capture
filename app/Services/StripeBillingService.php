<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

    public function createCheckoutSession(Account $account, string $priceId, ?string $planSlug = null): string
    {
        $customerId = $this->ensureCustomer($account);
        $successUrl = $this->buildSuccessUrl($planSlug);
        $cancelUrl = $this->buildCancelUrl($planSlug);

        $response = $this->request()
            ->withHeaders(['Idempotency-Key' => 'checkout:' . $account->id . ':' . $priceId . ':' . now()->format('YmdHi')])
            ->post('/v1/checkout/sessions', [
                'mode' => 'subscription',
                'customer' => $customerId,
                'client_reference_id' => $account->id,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'line_items[0][price]' => $priceId,
                'line_items[0][quantity]' => 1,
                'allow_promotion_codes' => 'true',
                'metadata[account_id]' => $account->id,
                'metadata[plan_slug]' => $planSlug ?? '',
                'subscription_data[metadata][account_id]' => $account->id,
                'subscription_data[metadata][plan_slug]' => $planSlug ?? '',
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

    /**
     * @return array<string, mixed>
     */
    public function getCheckoutSessionById(string $sessionId): array
    {
        $sessionId = trim($sessionId);

        if ($sessionId === '') {
            throw new RuntimeException('Stripe checkout session id is required.');
        }

        $response = $this->request()->get('/v1/checkout/sessions/' . $sessionId);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to fetch Stripe checkout session details.');
        }

        $session = $response->json();

        if (! is_array($session)) {
            throw new RuntimeException('Stripe checkout session response is invalid.');
        }

        return $session;
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

    /**
     * @return array<string, mixed>
     */
    public function getRecurringPriceById(string $priceId): array
    {
        $priceId = trim($priceId);

        if ($priceId === '') {
            throw new RuntimeException('Stripe price id is required.');
        }

        $response = $this->request()->get('/v1/prices/' . $priceId, [
            'expand[]' => 'product',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to fetch Stripe price details.');
        }

        $price = $response->json();

        if (! is_array($price) || (bool) data_get($price, 'active', false) !== true) {
            throw new RuntimeException('Stripe price is not active.');
        }

        if ((string) data_get($price, 'type', '') !== 'recurring') {
            throw new RuntimeException('Stripe price is not recurring.');
        }

        return $price;
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

    private function buildSuccessUrl(?string $planSlug): string
    {
        $baseUrl = (string) config('services.stripe.checkout_success_url');

        return $this->appendQuery($baseUrl, array_filter([
            'session_id' => '{CHECKOUT_SESSION_ID}',
            'plan' => $planSlug,
        ], static fn (mixed $value): bool => is_string($value) && $value !== ''));
    }

    private function buildCancelUrl(?string $planSlug): string
    {
        $baseUrl = (string) config('services.stripe.checkout_cancel_url');

        if (! is_string($planSlug) || $planSlug === '') {
            return $baseUrl;
        }

        return $this->appendQuery($baseUrl, [
            'plan' => $planSlug,
        ]);
    }

    /**
     * @param array<string, string> $query
     */
    private function appendQuery(string $url, array $query): string
    {
        if ($url === '' || $query === []) {
            return $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $existingQuery = [];

        if (isset($parts['query']) && is_string($parts['query'])) {
            parse_str($parts['query'], $existingQuery);
        }

        $mergedQuery = array_merge($existingQuery, $query);
        $rebuiltQuery = http_build_query($mergedQuery);

        $rebuiltUrl = '';

        if (isset($parts['scheme'])) {
            $rebuiltUrl .= $parts['scheme'] . '://';
        }

        if (isset($parts['user'])) {
            $rebuiltUrl .= $parts['user'];

            if (isset($parts['pass'])) {
                $rebuiltUrl .= ':' . $parts['pass'];
            }

            $rebuiltUrl .= '@';
        }

        if (isset($parts['host'])) {
            $rebuiltUrl .= $parts['host'];
        }

        if (isset($parts['port'])) {
            $rebuiltUrl .= ':' . $parts['port'];
        }

        $rebuiltUrl .= $parts['path'] ?? '';

        if ($rebuiltQuery !== '') {
            $rebuiltUrl .= '?' . $rebuiltQuery;
        }

        if (isset($parts['fragment'])) {
            $rebuiltUrl .= '#' . $parts['fragment'];
        }

        return Str::replace('%7BCHECKOUT_SESSION_ID%7D', '{CHECKOUT_SESSION_ID}', $rebuiltUrl);
    }
}
