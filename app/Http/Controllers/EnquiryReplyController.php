<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\Reply;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnquiryReplyController extends Controller
{
    public function store(Request $request, Enquiry $enquiry, AuditLogger $auditLogger): RedirectResponse
    {
        $this->authorizeAccountAccess($request, $enquiry->account_id);
        $this->authorizeForRequest($request, 'createReply', $enquiry);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $admin = Auth::guard('admin')->user();
        $user = Auth::guard('web')->user();

        abort_if($admin === null && $user === null, 401);

        $senderType = $admin ? 'administrator' : 'user';
        $senderId = $admin?->uuid;

        $metadata = [
            'admin_override' => $this->isAdminOverride($request),
        ];

        if ($user) {
            $metadata['user_id'] = $user->getAuthIdentifier();
        }

        Reply::query()->create([
            'enquiry_id' => $enquiry->id,
            'account_id' => $enquiry->account_id,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'email' => null,
            'content' => $data['content'],
            'is_internal' => (bool) ($data['is_internal'] ?? false),
            'metadata' => $metadata,
        ]);

        $auditLogger->log(
            $request,
            'replies.create',
            'enquiry',
            (string) $enquiry->uuid,
            $enquiry->account_id,
            ['admin_override' => $this->isAdminOverride($request)]
        );

        return redirect()
            ->route('inbox.show', $enquiry)
            ->with('status', 'Reply added.');
    }
}
