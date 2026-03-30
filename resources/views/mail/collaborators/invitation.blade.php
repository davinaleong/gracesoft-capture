<p>You have been invited to collaborate on a GraceSoft Capture account.</p>
<p><strong>Role:</strong> {{ $invitation->role }}</p>
<p><strong>Invitation expires:</strong> {{ optional($invitation->expires_at)->toDayDateTimeString() }}</p>
<p><a href="{{ $acceptUrl }}">Accept invitation</a></p>
<p>If you did not expect this invite, you can safely ignore this email.</p>
