@props([
    'action' => route('support.store'),
])

<form method="post" action="{{ $action }}" class="space-y-4">
    @csrf

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-ui.field for="name" label="Name" required>
            <x-ui.input id="name" name="name" :value="old('name')" required />
        </x-ui.field>

        <x-ui.field for="email" label="Email" required>
            <x-ui.input id="email" name="email" type="email" :value="old('email')" required />
        </x-ui.field>

        <x-ui.field for="subject" label="Subject" required class="md:col-span-2">
            <x-ui.select id="subject" name="subject" required>
                <option value="" @selected(old('subject') === '') disabled>Select a support topic</option>
                <option value="general_inquiry" @selected(old('subject') === 'general_inquiry')>General inquiry</option>
                <option value="technical_issue" @selected(old('subject') === 'technical_issue')>Technical issue</option>
                <option value="billing_payment" @selected(old('subject') === 'billing_payment')>Billing or payment</option>
                <option value="feature_request" @selected(old('subject') === 'feature_request')>Feature request</option>
                <option value="account_access" @selected(old('subject') === 'account_access')>Account access</option>
            </x-ui.select>
        </x-ui.field>

        <x-ui.field for="message" label="Message" required class="md:col-span-2">
            <x-ui.textarea id="message" name="message" rows="5" required>{{ old('message') }}</x-ui.textarea>
        </x-ui.field>

    </div>

    <div class="flex items-center gap-2">
        <x-ui.button type="submit">Send to Support</x-ui.button>
    </div>
</form>
