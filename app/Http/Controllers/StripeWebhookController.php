<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        if (! $this->hasValidSignature($request, $payload)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $event = $request->json()->all();
        $type = (string) Arr::get($event, 'type', '');

        match ($type) {
            'invoice.paid' => $this->handleInvoicePaid($event),
            'customer.subscription.updated', 'customer.subscription.deleted' => $this->handleSubscriptionUpdated($event),
            default => null,
        };

        return response()->json(['ok' => true]);
    }

    private function handleInvoicePaid(array $event): void
    {
        $object = (array) Arr::get($event, 'data.object', []);
        $subscriptionId = (string) Arr::get($object, 'subscription', '');
        $customerId = (string) Arr::get($object, 'customer', '');
        $periodEnd = Arr::get($object, 'lines.data.0.period.end');
        $priceId = (string) Arr::get($object, 'lines.data.0.price.id', '');

        if ($subscriptionId === '' || $customerId === '') {
            return;
        }

        $plan = $this->planByPriceId($priceId);

        DB::transaction(function () use ($customerId, $subscriptionId, $periodEnd, $plan): void {
            $account = Account::query()->where('stripe_customer_id', $customerId)->first();

            if (! $account) {
                return;
            }

            $subscription = Subscription::query()
                ->where('account_id', $account->id)
                ->where('stripe_subscription_id', $subscriptionId)
                ->latest('updated_at')
                ->first();

            if (! $subscription) {
                $subscription = Subscription::query()->create([
                    'id' => (string) Str::uuid(),
                    'account_id' => $account->id,
                    'plan_id' => $plan->id,
                    'stripe_subscription_id' => $subscriptionId,
                    'status' => 'active',
                    'current_period_end' => is_numeric($periodEnd) ? now()->setTimestamp((int) $periodEnd) : null,
                ]);

                return;
            }

            $subscription->update([
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_end' => is_numeric($periodEnd) ? now()->setTimestamp((int) $periodEnd) : $subscription->current_period_end,
            ]);
        });
    }

    private function handleSubscriptionUpdated(array $event): void
    {
        $object = (array) Arr::get($event, 'data.object', []);
        $subscriptionId = (string) Arr::get($object, 'id', '');
        $customerId = (string) Arr::get($object, 'customer', '');
        $status = (string) Arr::get($object, 'status', 'active');
        $periodEnd = Arr::get($object, 'current_period_end');
        $priceId = (string) Arr::get($object, 'items.data.0.price.id', '');

        if ($subscriptionId === '' || $customerId === '') {
            return;
        }

        $plan = $this->planByPriceId($priceId);

        DB::transaction(function () use ($subscriptionId, $customerId, $status, $periodEnd, $plan): void {
            $account = Account::query()->where('stripe_customer_id', $customerId)->first();

            if (! $account) {
                return;
            }

            $subscription = Subscription::query()
                ->where('account_id', $account->id)
                ->where('stripe_subscription_id', $subscriptionId)
                ->latest('updated_at')
                ->first();

            if (! $subscription) {
                Subscription::query()->create([
                    'id' => (string) Str::uuid(),
                    'account_id' => $account->id,
                    'plan_id' => $plan->id,
                    'stripe_subscription_id' => $subscriptionId,
                    'status' => $status,
                    'current_period_end' => is_numeric($periodEnd) ? now()->setTimestamp((int) $periodEnd) : null,
                ]);

                return;
            }

            $subscription->update([
                'plan_id' => $plan->id,
                'status' => $status,
                'current_period_end' => is_numeric($periodEnd) ? now()->setTimestamp((int) $periodEnd) : $subscription->current_period_end,
            ]);
        });
    }

    private function hasValidSignature(Request $request, string $payload): bool
    {
        $secret = (string) config('services.stripe.webhook_secret', '');

        if ($secret === '') {
            return false;
        }

        $header = (string) $request->header('Stripe-Signature', '');

        if ($header === '') {
            return false;
        }

        $parts = collect(explode(',', $header))
            ->map(fn (string $part): array => array_pad(explode('=', trim($part), 2), 2, ''))
            ->filter(fn (array $pair): bool => $pair[0] !== '' && $pair[1] !== '')
            ->mapWithKeys(fn (array $pair): array => [$pair[0] => $pair[1]]);

        $timestamp = $parts->get('t');
        $signature = $parts->get('v1');

        if (! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($signature) || $signature === '') {
            return false;
        }

        if (abs(now()->timestamp - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return hash_equals($expected, $signature);
    }

    private function planByPriceId(string $priceId): Plan
    {
        if ($priceId !== '') {
            $plan = Plan::query()->where('stripe_price_id', $priceId)->first();

            if ($plan) {
                return $plan;
            }
        }

        return Plan::query()->where('slug', 'free')->firstOrFail();
    }
}
