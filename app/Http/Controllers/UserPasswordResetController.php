<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class UserPasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.user-forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::broker('users')->sendResetLink($data);

        return back()->with('status', 'If the account exists, a reset link has been sent.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.user-reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('users')->reset(
            $data,
            function ($user) use ($data): void {
                $user->forceFill([
                    'password' => $data['password'],
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => 'Password reset token is invalid or expired.',
            ]);
        }

        return redirect()->route('login')->with('status', 'Password reset successful.');
    }
}
