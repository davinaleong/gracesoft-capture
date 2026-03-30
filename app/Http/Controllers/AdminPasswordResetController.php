<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AdminPasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.admin-forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::broker('administrators')->sendResetLink($data);

        return back()->with('status', 'If the administrator account exists, a reset link has been sent.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.admin-reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);

        $status = Password::broker('administrators')->reset(
            $data,
            function ($admin) use ($data): void {
                $admin->forceFill([
                    'password' => $data['password'],
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => 'Password reset token is invalid or expired.',
            ]);
        }

        return redirect()->route('admin.login')->with('status', 'Administrator password reset successful.');
    }
}
