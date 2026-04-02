@extends('layouts.auth')

@section('content')
    <x-ui.card class="space-y-5">
        <div>
            <h1 class="text-2xl font-bold text-gs-black-900">Privacy Policy</h1>
            <p class="mt-1 text-sm text-gs-black-600">Last updated: April 2, 2026</p>
        </div>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">1. What We Collect</h2>
            <p>We collect account details (name, email), workspace data, enquiry content submitted through forms, and usage events needed to operate the service.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">2. How We Use Data</h2>
            <p>We use data to deliver enquiry capture, collaboration, reply workflows, security controls, billing support, and service improvement.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">3. Sharing and Processors</h2>
            <p>We share data only with service providers needed to run the product (for example hosting, email delivery, and payment processing). We do not sell personal data.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">4. Data Retention</h2>
            <p>We retain data as long as needed for product operation, legal obligations, dispute handling, and security/audit requirements. Retention cleanup jobs may remove expired records.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">5. Security</h2>
            <p>We apply technical and organizational controls to protect data, including authentication, access context checks, and event logging.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">6. Your Rights</h2>
            <p>Depending on your location, you may have rights to access, correct, delete, or export your personal data. You may also object to or restrict certain processing.</p>
        </section>

        <section class="space-y-2 text-sm text-gs-black-700">
            <h2 class="text-base font-semibold text-gs-black-900">7. Contact</h2>
            <p>For privacy requests or questions, use the in-product support form on the Contact Support page.</p>
        </section>
    </x-ui.card>
@endsection
