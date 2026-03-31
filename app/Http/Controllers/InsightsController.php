<?php

namespace App\Http\Controllers;

use App\Services\InsightsService;
use App\Support\PlanGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InsightsController extends Controller
{
    public function index(Request $request, InsightsService $insightsService, PlanGate $planGate): View
    {
        $accountId = $this->resolvedAccountId($request);

        abort_unless(is_string($accountId) && $accountId !== '', 403, 'No account context selected.');

        if ((bool) config('capture.features.enforce_access_context', false)) {
            $actor = $this->authorizationActor($request);
            abort_if($actor === null, 401);
            Gate::forUser($actor)->authorize('insights.view-account', $accountId);
        }

        abort_unless($planGate->insightsEnabled($accountId), 403, 'Insights are available on Pro plans only.');

        $days = (int) $request->integer('days', 14);
        $days = in_array($days, [7, 14, 30], true) ? $days : 14;

        $summary = $insightsService->summaryForAccount($accountId, $days);

        return view('insights.index', [
            'accountId' => $accountId,
            'days' => $days,
            'summary' => $summary,
        ]);
    }
}
