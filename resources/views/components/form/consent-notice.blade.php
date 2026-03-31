<label class="flex items-start gap-2 text-sm text-gs-black-800" for="consent_accepted">
    <input
        id="consent_accepted"
        type="checkbox"
        name="consent_accepted"
        value="1"
        @checked(old('consent_accepted'))
        class="mt-1 h-4 w-4 rounded border-gray-300"
    >
    <span>I agree to the privacy notice and consent to processing my enquiry data.</span>
</label>
