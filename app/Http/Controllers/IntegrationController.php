<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Form::query()->latest();
        $resolvedAccountId = $this->resolvedAccountId($request);
        $selectedFormId = trim((string) $request->query('form_id', ''));

        if (! $this->isAdminOverride($request) && $resolvedAccountId !== null) {
            $query->where('account_id', $resolvedAccountId);
        } elseif ($resolvedAccountId !== null) {
            $query->where('account_id', $resolvedAccountId);
        }

        if (is_string($resolvedAccountId) && $resolvedAccountId !== '') {
            $this->authorizeAnyRole($request, ['owner', 'member', 'viewer'], $resolvedAccountId);
        }

        if (Str::isUuid($selectedFormId)) {
            $query->where('uuid', $selectedFormId);
        }

        return view('integrations.index', [
            'forms' => $query->paginate(15)->withQueryString(),
            'accountId' => $resolvedAccountId,
            'appDomain' => (string) parse_url((string) config('app.url', ''), PHP_URL_HOST),
            'selectedFormId' => Str::isUuid($selectedFormId) ? $selectedFormId : null,
        ]);
    }
}
