<x-mail::message>
# New form submission received

A new submission has been captured in GraceSoft Capture.

**Form:** {{ $formName }}

**From:** {{ $maskedName }} &lt;{{ $maskedEmail }}&gt;

**Subject:** {{ $subjectPreview }}

<x-mail::panel>
{{ $messagePreview }}
</x-mail::panel>

<x-mail::button :url="$inboxUrl">
Open Inbox
</x-mail::button>

If you did not expect this submission, review your form links and access settings.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
