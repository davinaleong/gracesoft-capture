@extends('layouts.app')

@section('content')
    @php
        $currentPlanSlug = optional(optional($currentSubscription ?? null)->plan)->slug;
        $currentPlanName = optional(optional($currentSubscription ?? null)->plan)->name;
        $highlightedUpgradePlan = is_string($highlightedUpgradePlan ?? null) ? $highlightedUpgradePlan : null;
        $recommendedUpgradeSlug = $highlightedUpgradePlan
            ?? (is_string($currentPlanSlug) && $currentPlanSlug === 'growth' ? 'pro' : 'growth');
        $recommendedPlan = collect($paidPlans ?? [])->firstWhere('slug', $recommendedUpgradeSlug);
        $planPriceLabels = [
            'growth' => '$9 / month',
            'pro' => '$29 / month',
        ];
    @endphp

    @if (is_string($billingAccountId ?? null) && $billingAccountId !== '')
        <x-ui.card id="upgrade-path-card" class="mb-4 space-y-3" tabindex="-1">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-gs-black-900">Subscription</h2>
                    <p class="text-sm text-gs-black-700">
                        Current plan:
                        <span class="font-semibold text-gs-black-900">{{ is_string($currentPlanName) && $currentPlanName !== '' ? $currentPlanName : 'Free' }}</span>
                    </p>
                </div>

                @if ($canManageBilling ?? false)
                    <form method="post" action="{{ route('billing.portal') }}">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $billingAccountId }}">
                        <x-ui.button type="submit" variant="secondary" size="sm">Manage Billing Portal</x-ui.button>
                    </form>
                @endif
            </div>

            @if ($canManageBilling ?? false)
                <div class="rounded border border-gs-black-200 bg-gs-black-50 p-4">
                    <p class="text-sm font-medium text-gs-black-900">Upgrade path</p>
                    <p class="mt-1 text-sm text-gs-black-700">Choose one plan and continue in Stripe checkout. Plan changes apply to this workspace immediately after payment.</p>

                    @if ($recommendedPlan)
                        @php
                            $isRecommendedCurrent = is_string($currentPlanSlug) && $currentPlanSlug === $recommendedPlan->slug;
                            $recommendedPrice = $planPriceLabels[$recommendedPlan->slug] ?? 'Price shown on next step';
                        @endphp

                        <x-ui.button
                            tag="a"
                            href="{{ route('billing.plan.show', ['plan' => $recommendedPlan->slug, 'account_id' => $billingAccountId]) }}"
                            class="mt-3 w-full justify-center"
                            :variant="$isRecommendedCurrent ? 'secondary' : 'primary'"
                        >
                            {{ $isRecommendedCurrent ? 'Manage ' . $recommendedPlan->name . ' (' . $recommendedPrice . ')' : 'Upgrade to ' . $recommendedPlan->name . ' (' . $recommendedPrice . ')' }}
                        </x-ui.button>
                    @endif

                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                        @foreach (($paidPlans ?? collect()) as $plan)
                            @php
                                $isCurrent = is_string($currentPlanSlug) && $currentPlanSlug === $plan->slug;
                                $priceLabel = $planPriceLabels[$plan->slug] ?? 'Price shown on next step';
                            @endphp

                            <x-ui.button
                                tag="a"
                                href="{{ route('billing.plan.show', ['plan' => $plan->slug, 'account_id' => $billingAccountId]) }}"
                                class="w-full justify-center"
                                variant="secondary"
                                size="sm"
                            >
                                {{ $isCurrent ? $plan->name . ' (Current - ' . $priceLabel . ')' : 'Choose ' . $plan->name . ' (' . $priceLabel . ')' }}
                            </x-ui.button>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="rounded border border-gs-black-200 bg-gs-black-50 px-3 py-2 text-sm text-gs-black-700">
                    Only workspace owners can change subscription plans from the dashboard.
                </p>
            @endif
        </x-ui.card>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.button tag="a" href="{{ route('manage.forms.create') }}">Create Form</x-ui.button>
    </div>

    <x-ui.card>
        <x-ui.table>
            <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                <tr>
                    <th class="p-2">Name</th>
                    <th class="p-2">Active</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($forms as $form)
                    <tr class="border-b border-gray-200">
                        <td class="p-2">{{ $form->name }}</td>
                        <td class="p-2">
                            <x-ui.badge :variant="$form->is_active ? 'success' : 'neutral'">
                                {{ $form->is_active ? 'Active' : 'Inactive' }}
                            </x-ui.badge>
                        </td>
                        <td class="p-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.button tag="a" href="{{ route('manage.forms.edit', $form) }}" variant="secondary" size="sm">Edit</x-ui.button>
                                <x-ui.button tag="a" href="{{ route('integrations.index', ['account_id' => $form->account_id, 'form_id' => $form->uuid]) }}" variant="secondary" size="sm">Integrate</x-ui.button>
                                <form method="post" action="{{ route('manage.forms.toggle-active', $form) }}" class="inline-flex">
                                    @csrf
                                    <x-ui.button type="submit" :variant="$form->is_active ? 'danger' : 'success'" size="sm">
                                        {{ $form->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-ui.button>
                                </form>
                                <x-ui.button tag="a" href="{{ route('forms.show', $form->public_token) }}" target="_blank" rel="noreferrer" variant="secondary" size="sm">
                                    <x-icons.eye size="16" />
                                    <span>Open Form</span>
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="p-6 text-center text-gs-black-600">
                            <p class="font-semibold text-gs-black-800">No forms created yet.</p>
                            <p class="mt-1 text-sm">Create your first form to start collecting enquiries.</p>
                            <div class="mt-3">
                                <x-ui.button tag="a" href="{{ route('manage.forms.create') }}" size="sm" class="px-4">Create Your First Form</x-ui.button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $forms->links() }}
        </div>
    </x-ui.card>

    @if ($highlightedUpgradePlan)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var upgradeCard = document.getElementById('upgrade-path-card');

                if (!upgradeCard) {
                    return;
                }

                upgradeCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                upgradeCard.focus({ preventScroll: true });
            });
        </script>
    @endif
@endsection
