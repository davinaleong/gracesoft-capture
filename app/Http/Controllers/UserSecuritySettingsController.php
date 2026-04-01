<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserSecuritySettingsController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        return view('settings.security', [
            'user' => $user,
            'twoFactorEnabled' => $user->two_factor_enabled_at !== null,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return back()->withErrors([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
        ])->save();

        return back()->with('status', 'Password updated successfully.');
    }

    public function toggleTwoFactor(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $data['enabled'];

        $user->forceFill([
            'two_factor_enabled_at' => $enabled ? now() : null,
        ])->save();

        return back()->with('status', $enabled ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.');
    }
}
