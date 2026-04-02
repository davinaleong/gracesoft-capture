<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DemoCaptureController extends Controller
{
    public function show(): View
    {
        return view('demo.free-plan');
    }

    public function submit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['nullable', 'max:0'],
        ]);

        $ttlMinutes = max((int) config('capture.features.demo_submission_ttl_minutes', 120), 5);
        $submissionId = (string) Str::uuid();

        // Demo submissions are intentionally short-lived and never stored in permanent tables.
        Cache::put('demo_submission:' . $submissionId, [
            'id' => $submissionId,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes($ttlMinutes)->toIso8601String(),
        ], now()->addMinutes($ttlMinutes));

        return redirect()
            ->route('demo.free.show')
            ->with('demo_submission_id', $submissionId)
            ->with('status', 'Demo submission received. This sample data auto-expires shortly and is not kept permanently.');
    }
}
