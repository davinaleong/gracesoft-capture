<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\Note;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EnquiryNoteController extends Controller
{
    public function store(Request $request, Enquiry $enquiry, PlanGate $planGate, AuditLogger $auditLogger): RedirectResponse
    {
        $this->authorizeAccountAccess($request, $enquiry->account_id);
        $this->authorizeForRequest($request, 'createNote', $enquiry);

        if (! $planGate->notesEnabled($enquiry->account_id)) {
            return back()->withErrors([
                'notes' => 'Notes are available on the Pro plan only.',
            ]);
        }

        $data = $request->validate([
            'user_id' => ['nullable', 'uuid'],
            'content' => ['required', 'string', 'max:5000'],
            'visibility' => ['nullable', 'in:internal,external'],
            'is_pinned' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string', 'max:255'],
            'reminder_at' => ['nullable', 'date'],
        ]);

        $noteUserId = $this->resolveNoteUserId($request, $data['user_id'] ?? null);
        $creatorName = $this->resolveNoteCreatorName($request);

        $tags = $this->parseTags($data['tags'] ?? null);

        Note::create([
            'enquiry_id' => $enquiry->id,
            'user_id' => $noteUserId,
            'creator_name' => $creatorName,
            'content' => $data['content'],
            'visibility' => $data['visibility'] ?? 'internal',
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'tags' => $tags->isNotEmpty() ? $tags->all() : null,
            'reminder_at' => $data['reminder_at'] ?? null,
        ]);

        $auditLogger->log(
            $request,
            'notes.create',
            'enquiry',
            (string) $enquiry->uuid,
            $enquiry->account_id,
            ['admin_override' => $this->isAdminOverride($request)]
        );

        return redirect()
            ->route('inbox.show', $enquiry)
            ->with('status', 'Note added.');
    }

    private function parseTags(?string $rawTags): Collection
    {
        return collect(explode(',', (string) $rawTags))
            ->map(static fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->take(10);
    }

    private function resolveNoteUserId(Request $request, mixed $candidate): string
    {
        if (is_string($candidate)) {
            $value = trim($candidate);

            if (Str::isUuid($value)) {
                return $value;
            }
        }

        $admin = $request->user('admin');

        if ($admin && is_string($admin->uuid ?? null) && Str::isUuid((string) $admin->uuid)) {
            return (string) $admin->uuid;
        }

        $hqAppId = trim((string) config('hq.credentials.app_id', ''));

        if (Str::isUuid($hqAppId)) {
            return $hqAppId;
        }

        return '00000000-0000-0000-0000-000000000001';
    }

    private function resolveNoteCreatorName(Request $request): string
    {
        $admin = $request->user('admin');

        if ($admin && is_string($admin->display_name ?? null) && trim((string) $admin->display_name) !== '') {
            return trim((string) $admin->display_name);
        }

        $user = $request->user('web');

        if ($user && is_string($user->name ?? null) && trim((string) $user->name) !== '') {
            return trim((string) $user->name);
        }

        return 'System';
    }
}
