<?php

namespace App\Http\Controllers;

use App\Jobs\SyncFeedbackToHQJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function create(): View
    {
        return view('support.contact');
    }

    public function createPanel(): View
    {
        return view('support.panel');
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->submitSupportRequest($request, null, 'support.create');
    }

    public function storePanel(Request $request): RedirectResponse
    {
        return $this->submitSupportRequest($request, $this->resolvedAccountId($request), 'panel.support.create');
    }

    private function submitSupportRequest(Request $request, ?string $accountId, string $redirectRoute): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'in:general_inquiry,technical_issue,billing_payment,feature_request,account_access'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $subjectLabels = [
            'general_inquiry' => 'General inquiry',
            'technical_issue' => 'Technical issue',
            'billing_payment' => 'Billing or payment',
            'feature_request' => 'Feature request',
            'account_access' => 'Account access',
        ];

        $subjectLabel = $subjectLabels[$data['subject']] ?? 'General inquiry';
        $supportRecipient = (string) config('capture.features.support_contact_email', 'support@gracesoft.dev');

        Mail::raw(
            "A new support request has been submitted.\n\n"
            . "From: {$data['name']} <{$data['email']}>\n"
            . "Subject: {$subjectLabel}\n\n"
            . "Message:\n{$data['message']}\n",
            function ($message) use ($data, $subjectLabel, $supportRecipient): void {
                $message
                    ->to($supportRecipient)
                    ->replyTo($data['email'], $data['name'])
                    ->subject('Support Request: ' . $subjectLabel);
            }
        );

        SyncFeedbackToHQJob::dispatch([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $subjectLabel,
            'message' => $data['message'],
            'account_id' => is_string($accountId) && $accountId !== '' ? $accountId : null,
            'app_name' => config('hq.credentials.app_name'),
            'occurred_at' => now()->toIso8601String(),
        ]);

        return redirect()
            ->route($redirectRoute)
            ->with('status', 'Support request submitted successfully.');
    }
}
