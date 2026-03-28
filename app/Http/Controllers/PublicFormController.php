<?php

namespace App\Http\Controllers;

use App\Jobs\SendEnquiryNotificationJob;
use App\Jobs\SyncAnalyticsEventToHQJob;
use App\Models\Enquiry;
use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicFormController extends Controller
{
    public function show(string $token): Response
    {
        $form = Form::query()
            ->where('public_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->view('form', [
            'form' => $form,
        ]);
    }

    public function submit(Request $request, string $token): RedirectResponse|Response
    {
        $form = Form::query()
            ->where('public_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['nullable', 'max:0'],
        ]);

        $enquiry = Enquiry::create([
            'form_id' => $form->id,
            'account_id' => $form->account_id,
            'application_id' => $form->application_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'new',
            'metadata' => [
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ],
        ]);

        $recipient = data_get($form->settings, 'notification_email') ?: config('mail.from.address');

        if (is_string($recipient) && $recipient !== '') {
            SendEnquiryNotificationJob::dispatch($enquiry->id, $recipient);
        }

        SyncAnalyticsEventToHQJob::dispatch([
            'event' => 'enquiry.created',
            'account_id' => $enquiry->account_id,
            'application_id' => $enquiry->application_id,
            'form_uuid' => $form->uuid,
            'enquiry_uuid' => $enquiry->uuid,
            'status' => $enquiry->status,
            'occurred_at' => now()->toIso8601String(),
        ]);

        if ($request->expectsJson()) {
            return response([
                'message' => 'Enquiry submitted successfully.',
            ], 201);
        }

        return redirect()
            ->route('forms.show', $form->public_token)
            ->with('status', 'Thanks, your message has been sent.');
    }
}
