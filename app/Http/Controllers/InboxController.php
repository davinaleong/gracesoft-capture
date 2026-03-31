<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        $query = Enquiry::query()
            ->with('form')
            ->when(in_array($status, ['new', 'contacted', 'closed'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->latest();

        $resolvedAccountId = $this->resolvedAccountId($request);

        if (! $this->isAdminOverride($request) && $resolvedAccountId !== null) {
            $query->where('account_id', $resolvedAccountId);
        } elseif ($resolvedAccountId !== null) {
            $query->where('account_id', $resolvedAccountId);
        }

        $enquiries = $query->paginate(20)->withQueryString();

        return view('inbox.index', [
            'enquiries' => $enquiries,
            'selectedStatus' => $status,
            'search' => $search,
            'accountId' => $resolvedAccountId,
        ]);
    }

    public function show(Request $request, Enquiry $enquiry, PlanGate $planGate, AuditLogger $auditLogger): View
    {
        $this->authorizeAccountAccess($request, $enquiry->account_id);

        if ($this->isAdminOverride($request)) {
            $this->requireAdminAccessReason($request);

            $auditLogger->logDataAccess(
                $request,
                'enquiry',
                (string) $enquiry->uuid,
                $enquiry->account_id,
                ['source' => 'inbox.show']
            );
        }

        $enquiry->load(['form', 'notes', 'replies']);

        $canReply = true;

        if ((bool) config('capture.features.enforce_access_context', false)) {
            $role = $this->resolvedMembershipRole($request, $enquiry->account_id);
            $canReply = $this->isAdminOverride($request) || in_array($role, ['owner', 'member'], true);
        }

        return view('inbox.show', [
            'enquiry' => $enquiry,
            'notesEnabled' => $planGate->notesEnabled($enquiry->account_id),
            'canReply' => $canReply,
        ]);
    }

    public function updateStatus(Request $request, Enquiry $enquiry, AuditLogger $auditLogger): RedirectResponse
    {
        $this->authorizeAccountAccess($request, $enquiry->account_id);
        $this->authorizeAnyRole($request, ['owner', 'member'], $enquiry->account_id);

        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
        ]);

        $nextStatus = $data['status'];

        if (! $this->isValidTransition($enquiry->status, $nextStatus)) {
            return back()->withErrors([
                'status' => 'Invalid status transition requested.',
            ]);
        }

        $updates = ['status' => $nextStatus];

        if ($nextStatus === 'contacted' && $enquiry->contacted_at === null) {
            $updates['contacted_at'] = now();
        }

        if ($nextStatus === 'closed' && $enquiry->closed_at === null) {
            $updates['closed_at'] = now();
        }

        $enquiry->update($updates);

        $auditLogger->log(
            $request,
            'enquiries.status.update',
            'enquiry',
            (string) $enquiry->uuid,
            $enquiry->account_id,
            [
                'from' => $enquiry->getOriginal('status'),
                'to' => $enquiry->status,
                'admin_override' => $this->isAdminOverride($request),
            ]
        );

        return redirect()
            ->route('inbox.show', $enquiry)
            ->with('status', 'Enquiry status updated.');
    }

    private function isValidTransition(string $current, string $target): bool
    {
        if ($current === $target) {
            return true;
        }

        $allowed = [
            'new' => ['contacted'],
            'contacted' => ['closed'],
            'closed' => [],
        ];

        return in_array($target, $allowed[$current] ?? [], true);
    }
}
