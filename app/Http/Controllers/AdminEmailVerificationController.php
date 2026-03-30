<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class AdminEmailVerificationController extends Controller
{
    public function notice(): View
    {
        return view('auth.admin-verify-email');
    }

    public function send(Request $request): RedirectResponse
    {
        $admin = $request->user('admin');

        if (! $admin) {
            abort(401);
        }

        if ($admin->hasVerifiedEmail()) {
            return redirect()->route('admin.compliance.index');
        }

        $admin->sendEmailVerificationNotification();

        return back()->with('status', 'Verification link sent.');
    }

    /**
     * @throws AuthorizationException
     */
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $admin = $request->user('admin');

        if (! $admin || $admin->getKey() !== $id) {
            abort(403);
        }

        if (! hash_equals((string) $hash, sha1($admin->getEmailForVerification()))) {
            abort(403);
        }

        if (! URL::hasValidSignature($request)) {
            abort(403);
        }

        if (! $admin->hasVerifiedEmail()) {
            $admin->markEmailAsVerified();
        }

        return redirect()->route('admin.compliance.index')->with('status', 'Administrator email verified successfully.');
    }
}
