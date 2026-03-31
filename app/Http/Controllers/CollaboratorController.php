<?php

namespace App\Http\Controllers;

use App\Jobs\SendCollaboratorInvitationJob;
use App\Models\AccountInvitation;
use App\Models\AccountMembership;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CollaboratorController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $accountId = $this->resolvedAccountId($request);

        abort_unless(is_string($accountId) && $accountId !== '', 403, 'No account context selected.');

        $membership = $this->resolveMembership($user->id, $accountId);

        $memberships = AccountMembership::query()
            ->where('account_id', $accountId)
            ->whereNull('removed_at')
            ->with('user')
            ->orderByRaw("case when role = 'owner' then 0 when role = 'member' then 1 else 2 end")
            ->orderBy('id')
            ->get();

        $invitations = AccountInvitation::query()
            ->where('account_id', $accountId)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->orderByDesc('id')
            ->get();

        return view('collaborators.index', [
            'accountId' => $accountId,
            'membership' => $membership,
            'memberships' => $memberships,
            'invitations' => $invitations,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger, PlanGate $planGate): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $data = $request->validate([
            'account_id' => ['required', 'uuid'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:owner,member,viewer'],
        ]);

        $membership = $this->resolveMembership($user->id, $data['account_id']);
        $this->authorizeOwner($membership->role);

        if (! $planGate->collaboratorInviteRoleAllowed($data['account_id'], $data['role'])) {
            return back()->withErrors([
                'role' => 'Your current plan does not allow inviting this collaborator role.',
            ])->withInput();
        }

        $existingMember = AccountMembership::query()
            ->where('account_id', $data['account_id'])
            ->whereNull('removed_at')
            ->whereHas('user', function ($query) use ($data) {
                $query->whereRaw('LOWER(email) = ?', [Str::lower($data['email'])]);
            })
            ->exists();

        if ($existingMember) {
            return back()->withErrors([
                'email' => 'This email is already a collaborator for the selected account.',
            ]);
        }

        $plainToken = Str::random(64);
        $hashedToken = hash('sha256', $plainToken);

        $expiresAt = now()->addHours((int) env('INVITE_TOKEN_TTL_HOURS', 48));

        $invitation = AccountInvitation::query()->create([
            'account_id' => $data['account_id'],
            'email' => Str::lower($data['email']),
            'role' => $data['role'],
            'invite_token' => $hashedToken,
            'invited_by_user_id' => $user->id,
            'expires_at' => $expiresAt,
        ]);

        $acceptUrl = URL::temporarySignedRoute(
            'collaborators.accept',
            $expiresAt,
            [
                'invitation' => $invitation->id,
                'token' => $plainToken,
            ]
        );

        SendCollaboratorInvitationJob::dispatch($invitation->id, $acceptUrl);

        $auditLogger->log(
            $request,
            'collaborators.invite.create',
            'account_invitation',
            (string) $invitation->id,
            $data['account_id'],
            [
                'email' => $invitation->email,
                'role' => $invitation->role,
            ]
        );

        return redirect()
            ->route('collaborators.index', ['account_id' => $data['account_id']])
            ->with('status', 'Invitation sent successfully.');
    }

    public function resend(Request $request, AccountInvitation $invitation, AuditLogger $auditLogger): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $membership = $this->resolveMembership($user->id, $invitation->account_id);
        $this->authorizeOwner($membership->role);

        if ($invitation->accepted_at !== null || $invitation->revoked_at !== null) {
            return back()->withErrors([
                'invitation' => 'Only pending invitations can be resent.',
            ]);
        }

        $plainToken = Str::random(64);

        $invitation->update([
            'invite_token' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours((int) env('INVITE_TOKEN_TTL_HOURS', 48)),
        ]);

        $acceptUrl = URL::temporarySignedRoute(
            'collaborators.accept',
            $invitation->expires_at,
            [
                'invitation' => $invitation->id,
                'token' => $plainToken,
            ]
        );

        SendCollaboratorInvitationJob::dispatch($invitation->id, $acceptUrl);

        $auditLogger->log(
            $request,
            'collaborators.invite.resend',
            'account_invitation',
            (string) $invitation->id,
            $invitation->account_id,
            ['email' => $invitation->email]
        );

        return back()->with('status', 'Invitation resent.');
    }

    public function revoke(Request $request, AccountInvitation $invitation, AuditLogger $auditLogger): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $membership = $this->resolveMembership($user->id, $invitation->account_id);
        $this->authorizeOwner($membership->role);

        if ($invitation->accepted_at !== null) {
            return back()->withErrors([
                'invitation' => 'Accepted invitations cannot be revoked.',
            ]);
        }

        $invitation->update([
            'revoked_at' => now(),
        ]);

        $auditLogger->log(
            $request,
            'collaborators.invite.revoke',
            'account_invitation',
            (string) $invitation->id,
            $invitation->account_id,
            ['email' => $invitation->email]
        );

        return back()->with('status', 'Invitation revoked.');
    }

    public function remove(Request $request, AccountMembership $membershipToRemove, AuditLogger $auditLogger): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $membership = $this->resolveMembership($user->id, $membershipToRemove->account_id);
        $this->authorizeOwner($membership->role);

        if ($membershipToRemove->role === 'owner') {
            return back()->withErrors([
                'membership' => 'Owner memberships cannot be removed from this screen.',
            ]);
        }

        if ((int) $membershipToRemove->user_id === (int) $user->id) {
            return back()->withErrors([
                'membership' => 'You cannot remove your own access.',
            ]);
        }

        $membershipToRemove->update([
            'removed_at' => now(),
        ]);

        $auditLogger->log(
            $request,
            'collaborators.membership.remove',
            'account_membership',
            (string) $membershipToRemove->id,
            $membershipToRemove->account_id,
            [
                'removed_user_id' => $membershipToRemove->user_id,
                'removed_role' => $membershipToRemove->role,
            ]
        );

        return back()->with('status', 'Collaborator removed from account.');
    }

    public function accept(Request $request, AccountInvitation $invitation, string $token, AuditLogger $auditLogger): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        if (! $request->hasValidSignature()) {
            $this->recordInvalidAcceptanceAttempt($request, $invitation, 'invalid_signature', $auditLogger);

            return redirect()->route('collaborators.index')->withErrors([
                'invitation' => 'Invitation link is invalid or expired.',
            ]);
        }

        if ($invitation->revoked_at !== null || $invitation->accepted_at !== null || $invitation->expires_at?->isPast()) {
            $this->recordInvalidAcceptanceAttempt($request, $invitation, 'invitation_inactive', $auditLogger);

            return redirect()->route('collaborators.index')->withErrors([
                'invitation' => 'Invitation is no longer valid.',
            ]);
        }

        if (! hash_equals($invitation->invite_token, hash('sha256', $token))) {
            $this->recordInvalidAcceptanceAttempt($request, $invitation, 'token_mismatch', $auditLogger);

            return redirect()->route('collaborators.index')->withErrors([
                'invitation' => 'Invitation token mismatch.',
            ]);
        }

        if (Str::lower($user->email) !== Str::lower($invitation->email)) {
            $this->recordInvalidAcceptanceAttempt($request, $invitation, 'email_mismatch', $auditLogger);

            return redirect()->route('collaborators.index')->withErrors([
                'invitation' => 'You must sign in with the invited email address.',
            ]);
        }

        AccountMembership::query()->updateOrCreate(
            [
                'account_id' => $invitation->account_id,
                'user_id' => $user->id,
            ],
            [
                'role' => $invitation->role,
                'invited_by_user_id' => $invitation->invited_by_user_id,
                'joined_at' => now(),
                'removed_at' => null,
            ]
        );

        $invitation->update([
            'accepted_at' => now(),
        ]);

        $auditLogger->log(
            $request,
            'collaborators.invite.accept',
            'account_invitation',
            (string) $invitation->id,
            $invitation->account_id,
            ['email' => $invitation->email]
        );

        return redirect()
            ->route('collaborators.index', ['account_id' => $invitation->account_id])
            ->with('status', 'Invitation accepted.');
    }

    private function resolveMembership(int $userId, string $accountId): AccountMembership
    {
        return AccountMembership::query()
            ->where('user_id', $userId)
            ->where('account_id', $accountId)
            ->whereNull('removed_at')
            ->firstOrFail();
    }

    private function authorizeOwner(string $role): void
    {
        abort_unless($role === 'owner', 403, 'Only account owners can manage collaborators.');
    }

    private function recordInvalidAcceptanceAttempt(Request $request, AccountInvitation $invitation, string $reason, AuditLogger $auditLogger): void
    {
        $windowMinutes = max((int) config('capture.features.collaborator_invite_alert_window_minutes', 30), 1);
        $threshold = max((int) config('capture.features.collaborator_invite_alert_threshold', 3), 1);
        $emailHash = hash('sha256', Str::lower((string) $invitation->email));
        $cacheKey = implode(':', [
            'capture',
            'collaborators',
            'invite',
            'invalid',
            $reason,
            (string) $invitation->id,
            (string) $request->ip(),
            $emailHash,
        ]);

        Cache::add($cacheKey, 0, now()->addMinutes($windowMinutes));
        $attempts = (int) Cache::increment($cacheKey);

        $metadata = [
            'reason' => $reason,
            'attempts' => $attempts,
            'window_minutes' => $windowMinutes,
        ];

        $auditLogger->log(
            $request,
            'collaborators.invite.accept.invalid',
            'account_invitation',
            (string) $invitation->id,
            $invitation->account_id,
            $metadata
        );

        if ($attempts >= $threshold) {
            $auditLogger->log(
                $request,
                'collaborators.invite.accept.alert',
                'account_invitation',
                (string) $invitation->id,
                $invitation->account_id,
                $metadata
            );
        }
    }
}
