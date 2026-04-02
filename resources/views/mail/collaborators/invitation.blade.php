<x-mail::message>
@php
	$resolvedRoleLabel = isset($roleLabel)
		? (string) $roleLabel
		: ucfirst((string) ($invitation->role ?? 'Member'));

	$resolvedExpiryLabel = isset($expiresAtLabel)
		? (string) $expiresAtLabel
		: (optional($invitation->expires_at)->toDayDateTimeString() ?: 'N/A');
@endphp

# Hello!

You have been invited to collaborate on a GraceSoft Capture account.

**Role:** {{ $resolvedRoleLabel }}

**Invitation expires:** {{ $resolvedExpiryLabel }}

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

If you did not expect this invite, you can safely ignore this email.

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
