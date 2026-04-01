@extends('layouts.app')

@section('content')
    @php
        $currentPlanSlug = optional(optional($currentSubscription ?? null)->plan)->slug;
        $currentPlanName = optional(optional($currentSubscription ?? null)->plan)->name;
    @endphp

    @if (is_string($billingAccountId ?? null) && $billingAccountId !== '')
        <x-ui.card class="mb-4 space-y-3">
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
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach (($paidPlans ?? collect()) as $plan)
                        @php
                            $isCurrent = is_string($currentPlanSlug) && $currentPlanSlug === $plan->slug;
                        @endphp
                        <div class="rounded border {{ $isCurrent ? 'border-gs-purple-300 bg-gs-purple-50' : 'border-gs-black-200 bg-white' }} p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-gs-black-900">{{ $plan->name }}</p>
                                    <p class="text-xs text-gs-black-700">Switch plan and continue checkout in Stripe.</p>
                                </div>
                                @if ($isCurrent)
                                    <x-ui.badge variant="primary">Current</x-ui.badge>
                                @endif
                            </div>

                            <form method="post" action="{{ route('billing.checkout') }}" class="mt-3">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->slug }}">
                                <input type="hidden" name="account_id" value="{{ $billingAccountId }}">
                                <x-ui.button type="submit" class="w-full justify-center" :variant="$isCurrent ? 'secondary' : 'primary'">
                                    {{ $isCurrent ? 'Renew or Manage ' . $plan->name : 'Switch to ' . $plan->name }}
                                </x-ui.button>
                            </form>
                        </div>
                    @endforeach
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
                                <x-ui.button tag="a" href="{{ route('integrations.index', ['account_id' => $form->account_id, 'form_id' => $form->id]) }}" variant="secondary" size="sm">Integrate</x-ui.button>
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
@endsection
