<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Form::query()->latest();
        $resolvedAccountId = $this->resolvedAccountId($request);

        if (! $this->isAdminOverride($request) && $resolvedAccountId !== null) {
            $query->where('account_id', $resolvedAccountId);
        } elseif ($resolvedAccountId !== null) {
            $query->where('account_id', $resolvedAccountId);
        }

        if (is_string($resolvedAccountId) && $resolvedAccountId !== '') {
            $this->authorizeAnyRole($request, ['owner', 'member', 'viewer'], $resolvedAccountId);
        }

        return view('integrations.index', [
            'forms' => $query->paginate(15)->withQueryString(),
            'appDomain' => (string) parse_url((string) config('app.url', ''), PHP_URL_HOST),
        ]);
    }
}
