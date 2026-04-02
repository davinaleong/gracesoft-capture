<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Plan;
use App\Models\StripeWebhookEvent;
use App\Models\Subscription;
use App\Services\StripeCatalogSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeCatalogSyncService $stripeCatalogSyncService)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        try {
            $event = $this->verifiedEvent($request, $payload);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $this->handleIdempotentEvent($event);

        return response()->json(['ok' => true]);
    }

    private function handleIdempotentEvent(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $record = StripeWebhookEvent::query()
                ->where('event_id', $event->id)
                ->lockForUpdate()
                ->first();

            if ($record?->processed_at !== null) {
                return;
            }

            if (! $record) {
                $record = StripeWebhookEvent::query()->create([
                    'event_id' => $event->id,
                    'event_type' => $event->type,
                    'processed_at' => null,
                ]);
            }

            $payload = $event->toArray();
            $type = (string) Arr::get($payload, 'type', '');

            match ($type) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($payload),
                'invoice.paid' => $this->handleInvoicePaid($payload),
                'customer.subscription.updated', 'customer.subscription.deleted' => $this->handleSubscriptionUpdated($payload),
                'product.created', 'product.updated' => $this->stripeCatalogSyncService->syncProduct((array) Arr::get($payload, 'data.object', [])),
                'price.created', 'price.updated' => $this->stripeCatalogSyncService->syncPrice((array) Arr::get($payload, 'data.object', [])),
                'product.deleted' => $this->stripeCatalogSyncService->clearDeletedProduct((string) Arr::get($payload, 'data.object.id', '')),
                'price.deleted' => $this->stripeCatalogSyncService->clearDeletedPrice((string) Arr::get($payload, 'data.object.id', '')),
                default => null,
            };

            $record->forceFill([
                'event_type' => $event->type,
                'processed_at' => now(),
            ])->save();
        });
    }

    private function handleCheckoutSessionCompleted(array $event): void
    {
        $object = (array) Arr::get($event, 'data.object', []);
        $customerId = (string) Arr::get($object, 'customer', '');
        $subscriptionId = (string) Arr::get($object, 'subscription', '');
        $accountUuid = $this->normalizeAccountUuid(
            Arr::get($object, 'metadata.account_uuid')
                ?? Arr::get($object, 'client_reference_id')
        );
        $planSlug = strtolower(trim((string) Arr::get($object, 'metadata.plan_slug', '')));

        if ($customerId === '' && $subscriptionId === '') {
            return;
        }

        $plan = $this->planBySlug($planSlug);

        DB::transaction(function () use ($customerId, $subscriptionId, $accountUuid, $plan): void {
            $account = $this->resolveAccount($customerId, $accountUuid);

            if (! $account) {
                return;
            }

            if ($customerId !== '' && $account->stripe_customer_id !== $customerId) {
                $account->forceFill(['stripe_customer_id' => $customerId])->save();
            }

            if ($subscriptionId === '') {
                return;
            }

            $subscription = Subscription::query()
                ->where('account_id', $account->id)
                ->where('stripe_subscription_id', $subscriptionId)
                ->latest('updated_at')
                ->first();

            if (! $subscription) {
                $subscription = Subscription::query()
                    ->where('account_id', $account->id)
                    ->whereIn('status', ['active', 'trialing', 'past_due'])
                    ->orderByDesc('updated_at')
                    ->first();
            }

            if (! $subscription) {
                Subscription::query()->create([
                    'id' => (string) Str::uuid(),
                    'account_id' => $account->id,
                    'plan_id' => $plan->id,
                    'stripe_subscription_id' => $subscriptionId,
                    'status' => 'active',
                    'current_period_end' => null,
                ]);

                return;
            }

            $subscription->update([
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $subscriptionId,
                'status' => 'active',
            ]);
        });
    }

    private function handleInvoicePaid(array $event): void
    {
        $object = (array) Arr::get($event, 'data.object', []);
        $subscriptionId = (string) Arr::get($object, 'subscription', '');
        $customerId = (string) Arr::get($object, 'customer', '');
        $accountUuid = $this->normalizeAccountUuid(
            Arr::get($object, 'metadata.account_uuid')
                ?? Arr::get($object, 'parent.subscription_details.metadata.account_uuid')
                ?? Arr::get($object, 'lines.data.0.metadata.account_uuid')
        );
        $periodEnd = Arr::get($object, 'lines.data.0.period.end');
        $priceId = (string) Arr::get($object, 'lines.data.0.price.id', '');

        if ($subscriptionId === '' || $customerId === '') {
            return;
        }

        $plan = $this->planByPriceId($priceId);

        DB::transaction(function () use ($customerId, $accountUuid, $subscriptionId, $periodEnd, $plan): void {
            $account = $this->resolveAccount($customerId, $accountUuid);

            if (! $account) {
                return;
            }

            if ($customerId !== '' && $account->stripe_customer_id !== $customerId) {
                $account->forceFill(['stripe_customer_id' => $customerId])->save();
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
        $accountUuid = $this->normalizeAccountUuid(Arr::get($object, 'metadata.account_uuid'));
        $status = (string) Arr::get($object, 'status', 'active');
        $periodEnd = Arr::get($object, 'current_period_end');
        $priceId = (string) Arr::get($object, 'items.data.0.price.id', '');

        if ($subscriptionId === '' || $customerId === '') {
            return;
        }

        $plan = $this->planByPriceId($priceId);

        DB::transaction(function () use ($subscriptionId, $customerId, $accountUuid, $status, $periodEnd, $plan): void {
            $account = $this->resolveAccount($customerId, $accountUuid);

            if (! $account) {
                return;
            }

            if ($customerId !== '' && $account->stripe_customer_id !== $customerId) {
                $account->forceFill(['stripe_customer_id' => $customerId])->save();
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

    private function verifiedEvent(Request $request, string $payload): Event
    {
        $secret = (string) config('services.stripe.webhook_secret', '');
        abort_if($secret === '', 500, 'Stripe webhook secret is not configured.');

        $header = (string) $request->header('Stripe-Signature', '');
        $tolerance = (int) config('services.stripe.webhook_tolerance_seconds', 300);

        return Webhook::constructEvent($payload, $header, $secret, $tolerance);
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

    private function planBySlug(string $slug): Plan
    {
        if ($slug !== '') {
            $plan = Plan::query()->where('slug', $slug)->first();

            if ($plan) {
                return $plan;
            }
        }

        return Plan::query()->where('slug', 'free')->firstOrFail();
    }

    private function resolveAccount(string $customerId, ?string $accountUuid): ?Account
    {
        if (is_string($accountUuid) && $accountUuid !== '') {
            $byUuid = Account::query()->find($accountUuid);

            if ($byUuid) {
                return $byUuid;
            }
        }

        if ($customerId === '') {
            return null;
        }

        return Account::query()->where('stripe_customer_id', $customerId)->first();
    }

    private function normalizeAccountUuid(mixed $candidate): ?string
    {
        if (! is_string($candidate)) {
            return null;
        }

        $value = trim($candidate);

        return Str::isUuid($value) ? $value : null;
    }
}
