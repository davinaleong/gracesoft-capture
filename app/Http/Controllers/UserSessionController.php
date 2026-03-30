<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\View\View;

class UserSessionController extends Controller
{
    public function register(): View
    {
        return view('auth.user-register');
    }

    public function storeRegistration(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::guard('admin')->logout();
        $request->session()->forget('admin.last_activity_at');

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->sendEmailVerificationNotification();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.guard_context', 'web');

        return redirect()->route('manage.forms.index')->with('status', 'Account created successfully.');
    }

    public function create(): View
    {
        return view('auth.user-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Keep user and admin sessions mutually exclusive.
        Auth::guard('admin')->logout();
        $request->session()->forget('admin.last_activity_at');

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'email' => 'Invalid user credentials.',
                ]);
        }

        $request->session()->regenerate();
        $request->session()->put('auth.guard_context', 'web');

        return redirect()->intended(route('manage.forms.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'User session signed out.');
    }
}
