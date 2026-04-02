@extends('layouts.auth')

@section('content')
    <x-ui.card class="space-y-6">
        <div class="space-y-2">
            <h1 class="text-2xl font-bold text-gs-black-900">Help Guide for Office Teams</h1>
            <p class="text-sm text-gs-black-700">
                This guide is written for everyday office users. Follow the steps in order and share this page with new teammates during onboarding.
            </p>
        </div>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-gs-black-900">Quick Start (First 15 Minutes)</h2>
            <ol class="list-decimal space-y-2 pl-5 text-sm text-gs-black-700">
                <li>Create your workspace from the registration page.</li>
                <li>Open Forms and create your first enquiry form.</li>
                <li>Open Integrations and copy the embed code to your website.</li>
                <li>Submit a test enquiry from the public form to verify capture.</li>
                <li>Open Inbox, assign status, and send a reply.</li>
            </ol>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-gs-black-900">Day-to-Day Workflow</h2>
            <div class="rounded border border-gs-black-100 bg-white p-4 text-sm text-gs-black-700">
                <p><span class="font-semibold">Morning:</span> Check Inbox for new items and set status to New, In Progress, or Resolved.</p>
                <p class="mt-2"><span class="font-semibold">During the day:</span> Add internal notes for context and send replies from the enquiry timeline.</p>
                <p class="mt-2"><span class="font-semibold">End of day:</span> Review unresolved items and add a note for handover if needed.</p>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-gs-black-900">Plan-Aware Features</h2>
            <div class="overflow-x-auto rounded border border-gs-black-100">
                <table class="min-w-full divide-y divide-gs-black-100 text-sm">
                    <thead class="bg-gs-black-50 text-left text-gs-black-700">
                        <tr>
                            <th class="px-3 py-2 font-semibold">Plan</th>
                            <th class="px-3 py-2 font-semibold">Best For</th>
                            <th class="px-3 py-2 font-semibold">What You Can Do</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gs-black-100 bg-white text-gs-black-700">
                        <tr>
                            <td class="px-3 py-2 font-semibold">Free</td>
                            <td class="px-3 py-2">Solo users</td>
                            <td class="px-3 py-2">Single inbox workflow, light follow-ups, basic capture volume.</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">Growth</td>
                            <td class="px-3 py-2">Small teams</td>
                            <td class="px-3 py-2">Shared team workflow, more volume, and faster collaboration.</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-semibold">Pro</td>
                            <td class="px-3 py-2">Operational teams</td>
                            <td class="px-3 py-2">Advanced collaboration, unlimited scale, and metrics visibility.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gs-black-600">
                If your team starts hitting limits, ask an owner to upgrade from the dashboard billing flow.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-gs-black-900">Common Questions</h2>
            <div class="space-y-3 text-sm text-gs-black-700">
                <div class="rounded border border-gs-black-100 bg-gs-black-50 p-3">
                    <p class="font-semibold text-gs-black-900">Where do I find an enquiry after someone submits the form?</p>
                    <p class="mt-1">Open Inbox and filter by newest items. All form submissions appear there automatically.</p>
                </div>
                <div class="rounded border border-gs-black-100 bg-gs-black-50 p-3">
                    <p class="font-semibold text-gs-black-900">Can I keep private internal notes?</p>
                    <p class="mt-1">Yes. Notes are for your team context and are not sent to the external contact.</p>
                </div>
                <div class="rounded border border-gs-black-100 bg-gs-black-50 p-3">
                    <p class="font-semibold text-gs-black-900">How do I get help fast?</p>
                    <p class="mt-1">Use the Contact Support page and include the issue, who is affected, and when it started.</p>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-2 border-t border-gs-black-100 pt-4">
            <x-ui.button tag="a" href="{{ route('support.create') }}">Contact Support</x-ui.button>
            <x-ui.button tag="a" href="{{ route('register') }}" variant="secondary">Create Workspace</x-ui.button>
            <x-ui.button tag="a" href="{{ url('/') }}" variant="neutral">Back to Landing</x-ui.button>
        </div>
    </x-ui.card>
@endsection
