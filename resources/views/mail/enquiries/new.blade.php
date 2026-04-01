<x-mail::message>
# A new support request has been submitted.

**From:** {{ $maskedName }} &lt;{{ $maskedEmail }}&gt;

**Subject:** {{ $subjectPreview }}

<x-mail::panel>
{{ $messagePreview }}
</x-mail::panel>
</x-mail::message>
