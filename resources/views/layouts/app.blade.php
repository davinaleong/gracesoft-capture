<!doctype html>
<html lang="en">
<head>
    @php
        $seoTitle = $title ?? 'GraceSoft Capture';
        $seoDescription = $metaDescription ?? 'GraceSoft Capture helps teams collect forms, replies, and customer feedback securely.';
        $seoCanonical = $canonicalUrl ?? url()->current();
        $seoImage = $metaImage ?? asset('og.png');
        $seoRobots = $metaRobots ?? 'index,follow';
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    <title>{{ $seoTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    @stack('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gs-black-50 text-gs-black-900">
    @php
        $user = auth('web')->user();
        $admin = auth('admin')->user();
        $showAdminLoginLinks = (bool) config('capture.features.show_admin_login_links', false);
        $enforceAccessContext = (bool) config('capture.features.enforce_access_context', false);
        $pageAccountId = $billingAccountId ?? $accountId ?? null;
        $activeAccountId = request()->attributes->get('access.account_id')
            ?? session('active_account_id')
            ?? $pageAccountId
            ?? request()->query('account_id');
        $activeAccountId = is_string($activeAccountId) ? trim($activeAccountId) : '';

        if ($activeAccountId === '' && $user) {
            $ownerAccountId = (string) \App\Models\AccountMembership::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->where('role', 'owner')
                ->whereNull('removed_at')
                ->value('account_id');

            if ($ownerAccountId !== '') {
                $activeAccountId = $ownerAccountId;
            } else {
                $memberAccountId = (string) \App\Models\AccountMembership::query()
                    ->where('user_id', $user->getAuthIdentifier())
                    ->whereNull('removed_at')
                    ->value('account_id');

                if ($memberAccountId !== '') {
                    $activeAccountId = $memberAccountId;
                }
            }
        }

        $routeName = (string) optional(request()->route())->getName();
        $currentTourStep = match (true) {
            str_starts_with($routeName, 'manage.forms.') => 'forms',
            str_starts_with($routeName, 'integrations.') => 'integrations',
            str_starts_with($routeName, 'inbox.') => 'inbox',
            str_starts_with($routeName, 'insights.') => 'insights',
            default => 'forms',
        };
        $tourAccountId = null;

        if (! $enforceAccessContext) {
            // In non-enforced mode, dashboards show workspace-wide data, so tour progress must match that same scope.
            $tourAccountId = '';
        } elseif ($activeAccountId !== '') {
            $tourAccountId = $activeAccountId;
        }

        $sharedTour = $user
            ? app(\App\Support\GuidedTour::class)->build($tourAccountId, $currentTourStep)
            : null;
        $showSetupSidebar = is_array($sharedTour) && is_array($sharedTour['nextStep'] ?? null);
    @endphp

    <main class="container mx-auto p-4 md:p-6 space-y-4">
        <x-ui.card class="p-3 md:p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="{{ route('manage.forms.index') }}" class="inline-flex items-center gap-2 rounded px-1 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gs-purple-300">
                        <img src="{{ asset('wm.svg') }}" alt="GraceSoft Capture" class="h-4 w-auto" />
                        <img src="{{ asset('beta.svg') }}" alt="Beta" class="h-3 w-auto" />
                    </a>

                    <nav class="hidden items-center gap-2 md:flex md:gap-3">
                        <x-ui.button tag="a" href="{{ route('manage.forms.index') }}" variant="secondary" size="sm">Forms</x-ui.button>
                        <x-ui.button tag="a" href="{{ route('inbox.index') }}" variant="secondary" size="sm">Inbox</x-ui.button>
                        <x-ui.button tag="a" href="{{ route('integrations.index') }}" variant="secondary" size="sm">Integrations</x-ui.button>
                        <x-ui.button tag="a" href="{{ route('collaborators.index') }}" variant="secondary" size="sm">Collaborators</x-ui.button>
                        <x-ui.button tag="a" href="{{ route('panel.support.create') }}" variant="secondary" size="sm">Contact Support</x-ui.button>
                    </nav>
                </div>

                <div class="md:hidden">
                    <x-ui.dropdown label="Menu" class="w-full">
                        <div class="flex min-w-40 flex-col gap-1">
                            <a class="rounded px-2 py-1 text-sm text-gs-black-800 hover:bg-gs-black-50" href="{{ route('manage.forms.index') }}">Forms</a>
                            <a class="rounded px-2 py-1 text-sm text-gs-black-800 hover:bg-gs-black-50" href="{{ route('inbox.index') }}">Inbox</a>
                            <a class="rounded px-2 py-1 text-sm text-gs-black-800 hover:bg-gs-black-50" href="{{ route('integrations.index') }}">Integrations</a>
                            <a class="rounded px-2 py-1 text-sm text-gs-black-800 hover:bg-gs-black-50" href="{{ route('collaborators.index') }}">Collaborators</a>
                            <a class="rounded px-2 py-1 text-sm text-gs-black-800 hover:bg-gs-black-50" href="{{ route('panel.support.create') }}">Contact Support</a>
                        </div>
                    </x-ui.dropdown>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($admin)
                        <span class="inline-flex h-9 items-center rounded border border-red-200 bg-red-50 px-2 text-xs font-semibold uppercase tracking-wide text-red-800">
                            Admin Session: {{ $admin->display_name }}
                        </span>
                        <form method="post" action="{{ route('admin.logout') }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="danger">Admin Logout</x-ui.button>
                        </form>
                    @elseif ($user)
                        <a href="{{ route('settings.security.index') }}" class="inline-flex h-9 items-center rounded border border-gs-purple-200 bg-gs-purple-50 px-2 text-xs font-semibold uppercase tracking-wide text-gs-purple-700 hover:bg-gs-purple-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gs-purple-300">
                            Account Settings
                        </a>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="neutral">Logout</x-ui.button>
                        </form>
                    @else
                        <x-ui.button tag="a" href="{{ route('login') }}" size="sm">User Login</x-ui.button>
                        @if ($showAdminLoginLinks)
                            <x-ui.button tag="a" href="{{ route('admin.login') }}" size="sm" variant="danger">Admin Login</x-ui.button>
                        @endif
                    @endif
                </div>
            </div>
        </x-ui.card>

        @if (session('status'))
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert variant="error">{{ $errors->first() }}</x-ui.alert>
        @endif

        <div class="{{ $showSetupSidebar ? 'grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_320px]' : '' }}">
            <div>
                @yield('content')
            </div>

            @if ($showSetupSidebar)
                <aside class="lg:sticky lg:top-6">
                    <x-onboarding.guided-tour :tour="$sharedTour" title="Setup progress" />
                </aside>
            @endif
        </div>
    </main>
</body>
</html>
