@extends('layouts.app')

@section('content')
    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card class="space-y-4 p-4">
            <div>
                <h2 class="text-lg font-semibold text-gs-black-900">Invite Collaborator</h2>
                <p class="text-sm text-gs-black-700">
                    @if ($accountId)
                        Invite by email and choose role access for account {{ $accountId }}.
                    @else
                        No account membership found for your user yet.
                    @endif
                </p>
                <x-collaborators.owner-only-banner :is-owner="$membership?->role === 'owner'" />
            </div>

            <form method="post" action="{{ route('collaborators.store') }}" class="space-y-3" id="invite-collaborator-form">
                @csrf
                @if ($accountId)
                    <input type="hidden" name="account_id" value="{{ $accountId }}">
                @endif

                <div>
                    <label class="mb-1 block text-sm text-gs-black-800" for="invite_email">Email</label>
                    <x-ui.input id="invite_email" type="email" name="email" value="{{ old('email') }}" required />
                </div>

                <div>
                    <label class="mb-1 block text-sm text-gs-black-800" for="invite_role">Role</label>
                    <x-ui.select id="invite_role" name="role" required>
                        <option value="member" @selected(old('role') === 'member')>Member</option>
                        <option value="viewer" @selected(old('role') === 'viewer')>Viewer</option>
                        <option value="owner" @selected(old('role') === 'owner')>Owner</option>
                    </x-ui.select>
                </div>

                <x-ui.button type="submit" :disabled="! $accountId || $membership?->role !== 'owner'">Send Invitation</x-ui.button>
            </form>

            <div class="space-y-2">
                <h3 class="text-md font-semibold text-gs-black-900">Pending Invitations</h3>
                @forelse ($invitations as $invitation)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded border border-gray-200 p-2">
                        <div>
                            <p class="text-sm text-gs-black-900">{{ $invitation->email }} ({{ $invitation->role }})</p>
                            <p class="text-xs text-gs-black-700">Expires {{ optional($invitation->expires_at)->diffForHumans() }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="post" action="{{ route('collaborators.resend', $invitation) }}">
                                @csrf
                                <x-ui.button type="submit" size="sm" variant="secondary" :disabled="$membership?->role !== 'owner'">Resend</x-ui.button>
                            </form>

                            <form method="post" action="{{ route('collaborators.revoke', $invitation) }}">
                                @csrf
                                <x-ui.button type="submit" size="sm" variant="danger" :disabled="$membership?->role !== 'owner'">Revoke</x-ui.button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gs-black-600">No pending invitations.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card class="space-y-3 p-4">
            <div>
                <h2 class="text-lg font-semibold text-gs-black-900">Collaborators</h2>
                <p class="text-sm text-gs-black-700">Account: {{ $accountId ?? 'Not selected' }}</p>
            </div>

            <x-ui.table>
                <thead class="bg-gray-50 uppercase text-xs tracking-wide text-gs-black-700">
                    <tr>
                        <th class="p-2 text-left">Name</th>
                        <th class="p-2 text-left">Email</th>
                        <th class="p-2 text-left">Role</th>
                        <th class="p-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($memberships as $entry)
                        <tr class="border-b border-gray-200">
                            <td class="p-2">{{ $entry->user?->name ?? 'Unknown' }}</td>
                            <td class="p-2">{{ $entry->user?->email ?? 'Unknown' }}</td>
                            <td class="p-2">
                                <x-ui.badge variant="neutral">{{ ucfirst($entry->role) }}</x-ui.badge>
                            </td>
                            <td class="p-2">
                                @if ($entry->role !== 'owner')
                                    <form method="post" action="{{ route('collaborators.remove', $entry) }}">
                                        @csrf
                                        <x-ui.button type="submit" size="sm" variant="danger" :disabled="$membership?->role !== 'owner'">Remove</x-ui.button>
                                    </form>
                                @else
                                    <span class="text-xs text-gs-black-600">Protected</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gs-black-600">No active collaborators.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
@endsection
