<?php

namespace App\Http\Controllers;

use App\Jobs\SyncFeedbackToHQJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function create(): View
    {
        return view('support.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'account_id' => ['nullable', 'uuid'],
        ]);

        SyncFeedbackToHQJob::dispatch([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'account_id' => $data['account_id'] ?? null,
            'app_name' => config('hq.credentials.app_name'),
            'occurred_at' => now()->toIso8601String(),
        ]);

        return redirect()
            ->route('support.create')
            ->with('status', 'Support request submitted successfully.');
    }
}
