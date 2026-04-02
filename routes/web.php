<?php

use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\AdminComplianceController;
use App\Http\Controllers\AdminEmailVerificationController;
use App\Http\Controllers\AdminPasswordResetController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\FormManagementController;
use App\Http\Controllers\EnquiryNoteController;
use App\Http\Controllers\EnquiryReplyController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserPasswordResetController;
use App\Http\Controllers\UserSecuritySettingsController;
use App\Http\Controllers\UserSessionController;
use App\Models\Account;
use App\Models\AccountMembership;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\StripeBillingService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function (StripeBillingService $stripeBillingService) {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.compliance.index');
    }

    $plans = Plan::query()
        ->whereIn('slug', ['free', 'growth', 'pro'])
        ->orderByRaw("CASE slug WHEN 'free' THEN 1 WHEN 'growth' THEN 2 WHEN 'pro' THEN 3 ELSE 99 END")
        ->get();

    $stripePrices = [];

    foreach ($plans as $plan) {
        $priceId = trim((string) $plan->stripe_price_id);

        if ($priceId === '') {
            continue;
        }

        try {
            $price = Cache::remember('landing-price:' . $priceId, now()->addMinutes(10), function () use ($stripeBillingService, $priceId): array {
                return $stripeBillingService->getRecurringPriceById($priceId);
            });

            $unitAmount = data_get($price, 'unit_amount');
            $currency = strtoupper((string) data_get($price, 'currency', 'USD'));
            $interval = (string) data_get($price, 'recurring.interval', 'month');
            $intervalCount = (int) data_get($price, 'recurring.interval_count', 1);

            if (is_int($unitAmount) || is_float($unitAmount) || (is_string($unitAmount) && is_numeric($unitAmount))) {
                $amount = ((int) $unitAmount) / 100;
                $amountLabel = number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);

                $suffix = '/mo';

                if ($interval === 'year') {
                    $suffix = '/yr';
                } elseif ($interval === 'week') {
                    $suffix = '/wk';
                } elseif ($interval === 'day') {
                    $suffix = '/day';
                }

                if ($intervalCount > 1) {
                    $suffix = '/'. $intervalCount . ' ' . $interval;
                }

                $stripePrices[$plan->id] = [
                    'primary' => '$' . $amountLabel,
                    'secondary' => $suffix,
                    'currency' => $currency,
                ];
            }
        } catch (\Throwable $exception) {
            Log::warning('Unable to resolve Stripe price for landing page.', [
                'plan_slug' => $plan->slug,
                'price_id' => $priceId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    return view('landing', [
        'plans' => $plans,
        'stripePrices' => $stripePrices,
    ]);
});

Route::get('/upgrade/{plan}', function (\Illuminate\Http\Request $request, string $plan) {
    abort_unless(in_array($plan, ['growth', 'pro'], true), 404);

    if (Auth::guard('web')->check()) {
        return redirect()
            ->route('manage.forms.index', ['upgrade' => $plan])
            ->with('status', 'Select your ' . ucfirst($plan) . ' upgrade in the dashboard.');
    }

    $request->session()->put('billing_upgrade_plan', $plan);

    return redirect()->route('register', ['plan' => $plan]);
})->name('billing.start');

// Backward-compatible fallback for any stale/root-posted checkout forms.
Route::post('/', [BillingController::class, 'checkout'])
    ->middleware('auth:web')
    ->name('billing.checkout.fallback');

Route::get('/components', function () {
    return view('components');
});

Route::get('/form/{token}', [PublicFormController::class, 'show'])
    ->name('forms.show');

Route::post('/form/{token}/submit', [PublicFormController::class, 'submit'])
    ->middleware('throttle:form-submissions')
    ->name('forms.submit');

Route::get('/support', [FeedbackController::class, 'create'])->name('support.create');
Route::post('/support', [FeedbackController::class, 'store'])->name('support.store');

Route::prefix('panel/support')->middleware(['auth.any', 'access.context'])->name('panel.support.')->group(function () {
    Route::get('/', [FeedbackController::class, 'createPanel'])->name('create');
    Route::post('/', [FeedbackController::class, 'storePanel'])->name('store');
});

Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms-and-conditions', 'legal.terms')->name('legal.terms');

Route::post('/sso/login', [SsoController::class, 'login'])->name('sso.login');

Route::post('/billing/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('billing.webhooks.stripe');

Route::get('/billing/success', function (\Illuminate\Http\Request $request, StripeBillingService $stripeBillingService) {
    $sessionId = trim((string) $request->query('session_id', ''));

    if (Auth::guard('web')->check()) {
        if ($sessionId !== '') {
            try {
                $session = $stripeBillingService->getCheckoutSessionById($sessionId);
                $status = (string) data_get($session, 'status', '');
                $paymentStatus = (string) data_get($session, 'payment_status', '');

                if ($status === 'complete' && in_array($paymentStatus, ['paid', 'no_payment_required'], true)) {
                    $subscriptionId = trim((string) data_get($session, 'subscription', ''));
                    $customerId = trim((string) data_get($session, 'customer', ''));
                    $planSlug = strtolower(trim((string) data_get($session, 'metadata.plan_slug', '')));
                    $resolvedPlan = in_array($planSlug, ['free', 'growth', 'pro'], true)
                        ? Plan::query()->where('slug', $planSlug)->first()
                        : null;
                    $fallbackFreePlan = Plan::query()->where('slug', 'free')->first();
                    $targetPlan = $resolvedPlan ?? $fallbackFreePlan;

                    $metadataAccountUuid = data_get($session, 'metadata.account_uuid')
                        ?? data_get($session, 'metadata.account_id')
                        ?? data_get($session, 'client_reference_id');

                    $candidateAccountId = is_string($metadataAccountUuid) && Str::isUuid(trim($metadataAccountUuid))
                        ? trim($metadataAccountUuid)
                        : null;

                    $sessionAccountId = (string) $request->session()->get('active_account_id', '');
                    if ($candidateAccountId === null && $sessionAccountId !== '' && Str::isUuid($sessionAccountId)) {
                        $candidateAccountId = $sessionAccountId;
                    }

                    $account = null;

                    if (is_string($candidateAccountId) && $candidateAccountId !== '') {
                        $account = Account::query()->find($candidateAccountId);
                    }

                    if (! $account && $customerId !== '') {
                        $account = Account::query()->where('stripe_customer_id', $customerId)->first();
                    }

                    if (! $account) {
                        $userId = (int) Auth::guard('web')->id();
                        $ownerAccountId = (string) AccountMembership::query()
                            ->where('user_id', $userId)
                            ->where('role', 'owner')
                            ->whereNull('removed_at')
                            ->value('account_id');

                        if ($ownerAccountId !== '' && Str::isUuid($ownerAccountId)) {
                            $account = Account::query()->find($ownerAccountId);
                        }
                    }

                    if ($account) {
                        if ($customerId !== '' && $account->stripe_customer_id !== $customerId) {
                            $account->forceFill(['stripe_customer_id' => $customerId])->save();
                        }

                        if ($subscriptionId !== '') {
                            $subscription = Subscription::query()
                                ->where('account_id', $account->id)
                                ->where('stripe_subscription_id', $subscriptionId)
                                ->latest('updated_at')
                                ->first();

                            if (! $subscription) {
                                $subscription = Subscription::query()
                                    ->where('account_id', $account->id)
                                    ->whereIn('status', ['active', 'trialing', 'past_due'])
                                    ->latest('updated_at')
                                    ->first();
                            }

                            if ($subscription) {
                                $updatePayload = [
                                    'stripe_subscription_id' => $subscriptionId,
                                    'status' => 'active',
                                ];

                                if ($targetPlan) {
                                    $updatePayload['plan_id'] = $targetPlan->id;
                                }

                                $subscription->update($updatePayload);
                            } else {
                                Subscription::query()->create([
                                    'id' => (string) Str::uuid(),
                                    'account_id' => $account->id,
                                    'plan_id' => $targetPlan?->id,
                                    'stripe_subscription_id' => $subscriptionId,
                                    'status' => 'active',
                                    'current_period_end' => null,
                                ]);
                            }
                        }
                    }

                    return redirect()->route('manage.forms.index')
                        ->with('status', 'Payment successful. Your billing update is active.');
                }
            } catch (\Throwable $exception) {
                Log::warning('Unable to verify Stripe checkout success session.', [
                    'session_id' => $sessionId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return redirect()->route('manage.forms.index')
            ->with('status', 'Checkout completed. Your billing update is being finalized.');
    }

    return view('billing.success');
})->name('billing.success');

Route::get('/billing/cancel', function (\Illuminate\Http\Request $request) {
    $candidatePlan = $request->query('plan');
    $plan = is_string($candidatePlan) && in_array($candidatePlan, ['growth', 'pro'], true)
        ? $candidatePlan
        : null;

    return view('billing.cancel', [
        'plan' => $plan,
    ]);
})->name('billing.cancel');

Route::prefix('billing')->middleware('auth:web')->name('billing.')->group(function () {
    Route::get('/plans/{plan}', [BillingController::class, 'showPlan'])->name('plan.show');
    Route::post('/checkout', [BillingController::class, 'checkout'])->name('checkout');
    Route::post('/portal', [BillingController::class, 'portal'])->name('portal');
});

Route::middleware('guest:web')->group(function () {
    Route::get('/register', [UserSessionController::class, 'register'])->name('register');
    Route::post('/register', [UserSessionController::class, 'storeRegistration'])->name('register.store');
    Route::get('/login', [UserSessionController::class, 'create'])->name('login');
    Route::post('/login', [UserSessionController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [UserPasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [UserPasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [UserPasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [UserPasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [UserSessionController::class, 'destroy'])
    ->middleware('auth:web')
    ->name('logout');

Route::middleware('auth:web')->group(function () {
    Route::get('/email/verify', [UserEmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::post('/email/verification-notification', [UserEmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', [UserEmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::prefix('settings/security')->name('settings.security.')->group(function () {
        Route::get('/', [UserSecuritySettingsController::class, 'index'])->name('index');
        Route::put('/password', [UserSecuritySettingsController::class, 'updatePassword'])->name('password.update');
        Route::post('/two-factor/toggle', [UserSecuritySettingsController::class, 'toggleTwoFactor'])->name('two-factor.toggle');
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
        Route::post('/login', [AdminSessionController::class, 'store'])->name('login.store');

        Route::get('/forgot-password', [AdminPasswordResetController::class, 'requestForm'])->name('password.request');
        Route::post('/forgot-password', [AdminPasswordResetController::class, 'sendResetLink'])->name('password.email');
        Route::get('/reset-password/{token}', [AdminPasswordResetController::class, 'resetForm'])->name('password.reset');
        Route::post('/reset-password', [AdminPasswordResetController::class, 'reset'])->name('password.update');
    });

    Route::post('/logout', [AdminSessionController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/email/verify', [AdminEmailVerificationController::class, 'notice'])
            ->name('verification.notice');
        Route::post('/email/verification-notification', [AdminEmailVerificationController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
        Route::get('/email/verify/{id}/{hash}', [AdminEmailVerificationController::class, 'verify'])
            ->middleware('signed')
            ->name('verification.verify');
    });
});

Route::prefix('manage/forms')->middleware(['auth.any', 'access.context'])->name('manage.forms.')->group(function () {
    Route::get('/', [FormManagementController::class, 'index'])->name('index');
    Route::get('/create', [FormManagementController::class, 'create'])->name('create');
    Route::post('/', [FormManagementController::class, 'store'])->name('store');
    Route::get('/{form}/edit', [FormManagementController::class, 'edit'])->name('edit');
    Route::put('/{form}', [FormManagementController::class, 'update'])->name('update');
    Route::post('/{form}/toggle-active', [FormManagementController::class, 'toggleActive'])->name('toggle-active');
});

Route::prefix('inbox')->middleware(['auth.any', 'access.context'])->name('inbox.')->group(function () {
    Route::get('/', [InboxController::class, 'index'])->name('index');
    Route::get('/{enquiry}', [InboxController::class, 'show'])->name('show');
    Route::post('/{enquiry}/status', [InboxController::class, 'updateStatus'])->name('status.update');
    Route::post('/{enquiry}/notes', [EnquiryNoteController::class, 'store'])->name('notes.store');
    Route::post('/{enquiry}/replies', [EnquiryReplyController::class, 'store'])->name('replies.store');
});

Route::prefix('insights')->middleware(['auth.any', 'access.context'])->name('insights.')->group(function () {
    Route::get('/', [InsightsController::class, 'index'])->name('index');
});

Route::prefix('integrations')->middleware(['auth.any', 'access.context'])->name('integrations.')->group(function () {
    Route::get('/', [IntegrationController::class, 'index'])->name('index');
});

Route::prefix('settings/collaborators')->middleware(['auth', 'access.context'])->name('collaborators.')->group(function () {
    Route::get('/', [CollaboratorController::class, 'index'])->name('index');
    Route::post('/', [CollaboratorController::class, 'store'])->name('store');
    Route::post('/{invitation}/resend', [CollaboratorController::class, 'resend'])->name('resend');
    Route::post('/{invitation}/revoke', [CollaboratorController::class, 'revoke'])->name('revoke');
    Route::post('/memberships/{membershipToRemove}/remove', [CollaboratorController::class, 'remove'])->name('remove');
});

Route::get('/collaborators/invitations/{invitation}/{token}/accept', [CollaboratorController::class, 'accept'])
    ->middleware(['auth', 'verified.guard:web,collaborator_acceptance'])
    ->name('collaborators.accept');

Route::prefix('admin/compliance')->middleware(['auth.any', 'access.context', 'admin.session.secure'])->name('admin.compliance.')->group(function () {
    Route::get('/', [AdminComplianceController::class, 'index'])->name('index');
    Route::post('/break-glass/request', [AdminComplianceController::class, 'requestBreakGlass'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('break-glass.request');
    Route::post('/break-glass/{breakGlassApproval}/approve', [AdminComplianceController::class, 'approveBreakGlass'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('break-glass.approve');
    Route::post('/dsr/{dataSubjectRequest}/status', [AdminComplianceController::class, 'updateDsrStatus'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('dsr.update');
    Route::post('/dsr/{dataSubjectRequest}/process', [AdminComplianceController::class, 'processDsr'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('dsr.process');
    Route::post('/administrators/{administrator}/recertify', [AdminComplianceController::class, 'recertifyAdministratorAccess'])
        ->middleware('verified.guard:admin,sensitive_admin_operation')
        ->name('administrators.recertify');
});
