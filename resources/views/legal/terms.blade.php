@extends('layouts.auth')

@section('content')
    <x-ui.card class="space-y-5">
        <div>
            <h1 class="text-2xl font-bold text-gs-black-900">Terms and Conditions</h1>
            <p class="mt-1 text-sm text-gs-black-600">Last updated: April 2, 2026</p>
        </div>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">1. Acceptance of Terms</h2>
            <p>By accessing or using GraceSoft Capture, you agree to these terms. If you do not agree, do not use the service.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">2. Accounts and Responsibilities</h2>
            <p>You are responsible for safeguarding account credentials, keeping workspace information accurate, and ensuring your use complies with applicable law.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">3. Acceptable Use</h2>
            <p>You must not use the service for unlawful, abusive, or harmful activity, including unauthorized access, malware distribution, or data misuse.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">4. Billing and Subscriptions</h2>
            <p>Paid features are plan-based. Subscription charges, renewal behavior, and cancellations are managed through the billing provider and in-product billing pages.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">5. Service Availability</h2>
            <p>We aim for reliable operation but do not guarantee uninterrupted availability. Maintenance, upgrades, and third-party outages may affect service.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">6. Intellectual Property</h2>
            <p>The service, branding, and software remain our property or our licensors' property. You retain ownership of your content and grant us rights needed to operate the service.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">7. Limitation of Liability</h2>
            <p>To the fullest extent allowed by law, we are not liable for indirect or consequential damages arising from your use of the service.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">8. Changes to Terms</h2>
            <p>We may update these terms from time to time. Continued use after updates means you accept the revised terms.</p>
        </section>
    </x-ui.card>
@endsection
