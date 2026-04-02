<x-mail::message>
# We replied to your enquiry

Thanks for contacting us. Here is the latest update from our team.

**Form:** {{ $formName }}

**Subject:** {{ $subjectPreview }}

<x-mail::panel>
{{ $replyPreview }}
</x-mail::panel>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
