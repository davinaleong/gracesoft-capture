@extends('layouts.app')

@section('content')
    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card class="space-y-4 p-4">
            <div>
                <h1 class="text-lg font-semibold text-gs-black-900">Account Security</h1>
                <p class="text-sm text-gs-black-700">Manage your password and two-factor authentication settings.</p>
            </div>

            <x-ui.field for="account_email" label="Email">
                <x-ui.input
                    id="account_email"
                    name="account_email"
                    type="email"
                    :value="(string) $user->email"
                    readonly
                    aria-readonly="true"
                />
            </x-ui.field>

            <form method="post" action="{{ route('settings.security.password.update') }}" class="space-y-3">
                @csrf
                @method('put')

                <x-ui.field for="current_password" label="Current Password" required>
                    <x-ui.input id="current_password" name="current_password" type="password" required />
                </x-ui.field>

                <x-ui.field for="password" label="New Password" required>
                    <x-ui.input id="password" name="password" type="password" required />
                </x-ui.field>

                <x-ui.field for="password_confirmation" label="Confirm New Password" required>
                    <x-ui.input id="password_confirmation" name="password_confirmation" type="password" required />
                </x-ui.field>

                <x-ui.button type="submit">Change Password</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card class="space-y-4 p-4">
            <div>
                <h2 class="text-lg font-semibold text-gs-black-900">Two-Factor Authentication</h2>
                <p class="text-sm text-gs-black-700">
                    Status:
                    @if ($twoFactorEnabled)
                        <span class="font-semibold text-green-700">Enabled</span>
                    @else
                        <span class="font-semibold text-gs-black-700">Disabled</span>
                    @endif
                </p>
            </div>

            <form method="post" action="{{ route('settings.security.two-factor.toggle') }}" class="space-y-3">
                @csrf

                <input type="hidden" name="enabled" value="{{ $twoFactorEnabled ? 0 : 1 }}">

                @if ($twoFactorEnabled)
                    <x-ui.button type="submit" variant="danger">Disable 2FA</x-ui.button>
                @else
                    <x-ui.button type="submit" variant="success">Enable 2FA</x-ui.button>
                @endif
            </form>
        </x-ui.card>

        <x-ui.card class="space-y-3 p-4 lg:col-span-2">
            <div>
                <h2 class="text-lg font-semibold text-gs-black-900">Subscription</h2>
                <p class="text-sm text-gs-black-700">Current billing plan and renewal context for this workspace.</p>
            </div>

            @php
                $planName = (string) (optional(optional($currentSubscription ?? null)->plan)->name ?? 'Free');
                $subscriptionStatus = (string) (($currentSubscription->status ?? '') ?: 'none');
                $periodEnd = optional($currentSubscription ?? null)->current_period_end;
            @endphp

            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded border border-gs-black-200 bg-gs-black-50 px-3 py-2">
                    <p class="text-xs uppercase tracking-wide text-gs-black-600">Plan</p>
                    <p class="text-sm font-semibold text-gs-black-900">{{ $planName }}</p>
                </div>
                <div class="rounded border border-gs-black-200 bg-gs-black-50 px-3 py-2">
                    <p class="text-xs uppercase tracking-wide text-gs-black-600">Status</p>
                    <p class="text-sm font-semibold text-gs-black-900">{{ $subscriptionStatus }}</p>
                </div>
                <div class="rounded border border-gs-black-200 bg-gs-black-50 px-3 py-2">
                    <p class="text-xs uppercase tracking-wide text-gs-black-600">Current period ends</p>
                    <p class="text-sm font-semibold text-gs-black-900">{{ $periodEnd ? $periodEnd->format('Y-m-d H:i') : 'N/A' }}</p>
                </div>
            </div>

            @if (is_string($billingAccountId ?? null) && $billingAccountId !== '')
                <p class="text-xs text-gs-black-600">Workspace: {{ $billingAccountId }}</p>
            @endif
        </x-ui.card>
    </div>
@endsection
