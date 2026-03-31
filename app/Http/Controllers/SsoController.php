<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        if (! (bool) config('capture.features.sso_enabled', false)) {
            abort(404);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
            'timestamp' => ['required', 'integer'],
            'signature' => ['required', 'string'],
        ]);

        $secret = (string) config('capture.features.sso_shared_secret', '');
        abort_if($secret === '', 422, 'SSO is not configured.');

        $ttl = max((int) config('capture.features.sso_signature_ttl_seconds', 300), 30);
        abort_if(abs(now()->timestamp - (int) $data['timestamp']) > $ttl, 403, 'SSO token expired.');

        $payload = sprintf('%s|%d', strtolower((string) $data['email']), (int) $data['timestamp']);
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        abort_unless(hash_equals($expectedSignature, (string) $data['signature']), 403, 'Invalid SSO signature.');

        $user = User::query()->firstOrCreate(
            ['email' => strtolower((string) $data['email'])],
            [
                'name' => (string) ($data['name'] ?? 'SSO User'),
                'password' => bcrypt(str()->random(40)),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::guard('admin')->logout();
        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect()->route('manage.forms.index');
    }
}
