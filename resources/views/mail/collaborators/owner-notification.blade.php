<p>Collaborator invitation update for your workspace.</p>

<p>
    Invitation for <strong>{{ $invitationEmail }}</strong> (role: {{ $invitationRole }})
    @if ($eventType === 'accepted')
        has been accepted.
    @else
        has been revoked.
    @endif
</p>
