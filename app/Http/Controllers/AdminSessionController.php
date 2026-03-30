<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.admin-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Keep user and admin sessions mutually exclusive.
        Auth::guard('web')->logout();
        $request->session()->forget('active_account_id');

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'email' => 'Invalid administrator credentials.',
                ]);
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin || $admin->status !== 'active') {
            Auth::guard('admin')->logout();

            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'email' => 'Administrator account is not active.',
                ]);
        }

        $request->session()->regenerate();
        $request->session()->put('auth.guard_context', 'admin');
        $request->session()->put('admin.last_activity_at', now()->timestamp);

        return redirect()->intended(route('admin.compliance.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Administrator session signed out.');
    }
}
