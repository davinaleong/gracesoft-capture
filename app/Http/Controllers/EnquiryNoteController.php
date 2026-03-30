<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\Note;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnquiryNoteController extends Controller
{
    public function store(Request $request, Enquiry $enquiry, PlanGate $planGate, AuditLogger $auditLogger): RedirectResponse
    {
        $this->authorizeAccountAccess($request, $enquiry->account_id);

        if (! $planGate->notesEnabled($enquiry->account_id)) {
            return back()->withErrors([
                'notes' => 'Notes are available on the Pro plan only.',
            ]);
        }

        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        Note::create([
            'enquiry_id' => $enquiry->id,
            'user_id' => $data['user_id'],
            'content' => $data['content'],
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
}
