<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserEmailVerificationController extends Controller
{
    public function notice(): View
    {
        return view('auth.user-verify-email');
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user('web');

        if (! $user) {
            abort(401);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('manage.forms.index');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Verification link sent.');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('manage.forms.index')->with('status', 'Email verified successfully.');
    }
}
