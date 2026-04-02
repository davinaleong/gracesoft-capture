<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GraceSoft | Customer Enquiries, Closed-Loop</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --landing-primary: #2563eb;
            --landing-ink: #000000;
            --landing-soft: #eff6ff;
            --landing-line: #bfdbfe;
        }

        @keyframes riseIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes glowPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.26;
            }
            50% {
                transform: scale(1.12);
                opacity: 0.4;
            }
        }

        .landing-reveal {
            animation: riseIn 700ms ease-out both;
        }

        .landing-reveal-delay-1 {
            animation-delay: 120ms;
        }

        .landing-reveal-delay-2 {
            animation-delay: 240ms;
        }

        .landing-glow {
            animation: glowPulse 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-white text-gs-black-900 antialiased">
    @php
        $webUser = auth('web')->user();
        $showAdminLoginLinks = (bool) config('capture.features.show_admin_login_links', false);
        $plans = isset($plans) ? $plans : collect();
        $stripePrices = isset($stripePrices) && is_array($stripePrices) ? $stripePrices : [];
        $planMarketing = [
            'free' => [
                'headline' => 'Get started, stay organized',
                'items' => [
                    '1 personal inbox',
                    'Up to 100 captured items',
                    'Light follow-ups',
                    'Simple, distraction-free capture',
                ],
                'best_for' => 'Best for individuals getting their workflow in place',
            ],
            'growth' => [
                'headline' => 'Collaborate and scale your workflow',
                'items' => [
                    'Up to 5 collaborators in one inbox',
                    'Up to 1,000 captured items',
                    'Up to 10,000 follow-ups',
                    'Shared workflow across your team',
                    'Basic support',
                ],
                'best_for' => 'Best for small teams managing real work together',
            ],
            'pro' => [
                'headline' => 'Operate with clarity and insight',
                'items' => [
                    'Up to 20 collaborators in one inbox',
                    'Unlimited capture and follow-ups',
                    'Priority support',
                    'Metrics dashboard (understand where time goes)',
                    'Attach notes to any item or reply for full context',
                ],
                'best_for' => 'Best for teams who want visibility, accountability, and optimization',
            ],
        ];
        $planPriceLabels = [
            'free' => '$0',
            'growth' => '$9',
            'pro' => '$29',
        ];
    @endphp

    <div class="relative overflow-hidden">
        <div aria-hidden="true" class="landing-glow pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gs-purple-200 blur-3xl"></div>
        <div aria-hidden="true" class="landing-glow pointer-events-none absolute -bottom-20 -left-12 h-72 w-72 rounded-full bg-gs-black-100 blur-3xl"></div>

        <header class="relative z-10 border-b border-gs-black-100 bg-white/85 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4 md:px-6">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('wm.svg') }}" alt="GraceSoft" class="h-8 w-auto">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-gs-black-500"></span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($webUser)
                        <x-ui.button tag="a" href="{{ route('manage.forms.index') }}" variant="secondary" size="sm">Go to Dashboard</x-ui.button>
                    @else
                        <x-ui.button tag="a" href="{{ route('login') }}" variant="secondary" size="sm">User Login</x-ui.button>
                        <x-ui.button tag="a" href="{{ route('register') }}" size="sm">Start Free</x-ui.button>
                    @endif
                    @if ($showAdminLoginLinks && ! $webUser)
                        <x-ui.button tag="a" href="{{ route('admin.login') }}" variant="danger" size="sm">Admin</x-ui.button>
                    @endif
                </div>
            </div>
        </header>

        <main>
            <section class="relative z-10 mx-auto grid w-full max-w-6xl gap-10 px-4 pb-14 pt-16 md:grid-cols-2 md:px-6 md:pb-20 md:pt-20">
                <div>
                    <p class="landing-reveal inline-flex rounded-full border border-gs-purple-200 bg-gs-purple-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-gs-purple-700">
                        Built for office teams
                    </p>
                    <h1 class="landing-reveal landing-reveal-delay-1 mt-4 text-4xl font-semibold leading-tight text-gs-black-950 md:text-5xl">
                        Capture every support request before it slips through.
                    </h1>
                    <p class="landing-reveal landing-reveal-delay-2 mt-4 max-w-xl text-base leading-relaxed text-gs-black-700 md:text-lg">
                        GraceSoft turns scattered enquiries into a structured inbox, complete with form embeds, ownership, and follow-up workflows your team can run in minutes.
                    </p>

                    <div class="landing-reveal landing-reveal-delay-2 mt-7 flex flex-wrap items-center gap-3">
                        <x-ui.button tag="a" href="{{ route('register') }}" size="lg">Create Workspace</x-ui.button>
                        <x-ui.button tag="a" href="{{ route('support.create') }}" variant="secondary" size="lg">See Support Form</x-ui.button>
                    </div>

                    <div class="mt-8 grid grid-cols-3 gap-3">
                        <div class="rounded-xl border border-gs-black-100 bg-white p-3 shadow-sm">
                            <p class="text-2xl font-semibold text-gs-black-950">2 min</p>
                            <p class="text-xs text-gs-black-600">to publish your first form</p>
                        </div>
                        <div class="rounded-xl border border-gs-black-100 bg-white p-3 shadow-sm">
                            <p class="text-2xl font-semibold text-gs-black-950">100%</p>
                            <p class="text-xs text-gs-black-600">centralized enquiry trail</p>
                        </div>
                        <div class="rounded-xl border border-gs-black-100 bg-white p-3 shadow-sm">
                            <p class="text-2xl font-semibold text-gs-black-950">24/7</p>
                            <p class="text-xs text-gs-black-600">capture from your website</p>
                        </div>
                    </div>
                </div>

                <aside class="relative rounded-2xl border border-gs-black-100 bg-[linear-gradient(160deg,var(--landing-soft),#ffffff)] p-5 shadow-[0_20px_50px_rgba(37,99,235,0.16)] md:p-6">
                    <div class="mb-4 flex items-center justify-between border-b border-gs-black-100 pb-3">
                        <p class="text-sm font-semibold text-gs-black-900">Live Operations Snapshot</p>
                        <span class="rounded-full bg-gs-purple-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-gs-purple-700">Today</span>
                    </div>

                    <div class="space-y-3">
                        <div class="rounded-xl border border-gs-purple-200 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-gs-black-900">New Enquiries</p>
                                <span class="text-sm font-semibold text-gs-purple-700">18</span>
                            </div>
                            <div class="mt-2 h-2 w-full rounded-full bg-gs-black-100">
                                <div class="h-2 w-[78%] rounded-full bg-gs-purple-600"></div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gs-black-200 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-gs-black-900">Replies Sent</p>
                                <span class="text-sm font-semibold text-gs-black-800">14</span>
                            </div>
                            <div class="mt-2 h-2 w-full rounded-full bg-gs-black-100">
                                <div class="h-2 w-[60%] rounded-full bg-gs-black-900"></div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gs-black-200 bg-white p-4">
                            <p class="text-sm text-gs-black-700">
                                "The team stopped missing inbox follow-ups in week one."
                            </p>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-gs-black-500">Ops Lead, Service Team</p>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="border-y border-gs-black-100 bg-gs-black-50">
                <div class="mx-auto grid w-full max-w-6xl gap-4 px-4 py-10 md:grid-cols-3 md:px-6">
                    <article class="rounded-xl border border-gs-black-100 bg-white p-5">
                        <p class="text-sm font-semibold text-gs-purple-700">1. Capture</p>
                        <h2 class="mt-1 text-lg font-semibold">Embed-ready forms</h2>
                        <p class="mt-2 text-sm text-gs-black-700">Generate iframe code instantly and publish on any page without engineering dependency.</p>
                    </article>
                    <article class="rounded-xl border border-gs-black-100 bg-white p-5">
                        <p class="text-sm font-semibold text-gs-purple-700">2. Triage</p>
                        <h2 class="mt-1 text-lg font-semibold">Team inbox workflow</h2>
                        <p class="mt-2 text-sm text-gs-black-700">Track status, add notes, and keep every update in one timeline your whole office can understand.</p>
                    </article>
                    <article class="rounded-xl border border-gs-black-100 bg-white p-5">
                        <p class="text-sm font-semibold text-gs-purple-700">3. Improve</p>
                        <h2 class="mt-1 text-lg font-semibold">Visibility that drives action</h2>
                        <p class="mt-2 text-sm text-gs-black-700">See response volume trends and collaboration patterns so you can improve turnaround time.</p>
                    </article>
                </div>
            </section>

            @if ($plans->isNotEmpty())
                <section class="mx-auto w-full max-w-6xl px-4 py-14 md:px-6">
                    <div class="mb-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gs-purple-700">Pricing plans</p>
                        <h2 class="mt-2 text-3xl font-semibold text-gs-black-950">Choose the plan that fits your team</h2>
                        <p class="mt-2 text-sm text-gs-black-700">Plans are connected to your existing billing setup. Paid plans start checkout instantly for signed-in users.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        @foreach ($plans as $plan)
                            @php
                                $slug = (string) $plan->slug;
                                $isPro = $slug === 'pro';
                                $isFree = $slug === 'free';
                                $priceLabel = $planPriceLabels[$slug] ?? 'Paid';
                                $marketing = $planMarketing[$slug] ?? [
                                    'headline' => 'Flexible plan for customer enquiry operations.',
                                    'items' => [],
                                    'best_for' => 'Best for teams running customer workflows.',
                                ];
                                $resolvedStripePrice = $stripePrices[$plan->id] ?? null;
                                $displayPrimary = is_array($resolvedStripePrice) ? (string) ($resolvedStripePrice['primary'] ?? $priceLabel) : $priceLabel;
                                $displaySecondary = is_array($resolvedStripePrice)
                                    ? (string) ($resolvedStripePrice['secondary'] ?? ($isFree ? '/month' : 'checkout'))
                                    : ($isFree ? '/month' : 'checkout');
                            @endphp

                            <article class="rounded-2xl border {{ $isPro ? 'border-gs-purple-300 shadow-[0_10px_32px_rgba(37,99,235,0.15)]' : 'border-gs-black-100' }} bg-white p-5">
                                @if ($isPro)
                                    <span class="inline-flex rounded-full bg-gs-purple-50 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-gs-purple-700">Most powerful</span>
                                @endif

                                <h3 class="mt-2 text-xl font-semibold text-gs-black-950">{{ $plan->name }}</h3>
                                <p class="mt-1 text-sm font-medium text-gs-black-800">{{ $marketing['headline'] }}</p>

                                <div class="mt-4 flex items-end gap-2">
                                    <p class="text-3xl font-semibold text-gs-black-950">{{ $displayPrimary }}</p>
                                    <p class="pb-1 text-sm text-gs-black-600">{{ $displaySecondary }}</p>
                                </div>

                                <ul class="mt-4 space-y-2 text-sm text-gs-black-700">
                                    @foreach ($marketing['items'] as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>

                                <p class="mt-4 rounded border border-gs-purple-200 bg-gs-purple-50 px-3 py-2 text-xs font-medium text-gs-purple-700">
                                    {{ $marketing['best_for'] }}
                                </p>

                                <div class="mt-5">
                                    @if ($isFree)
                                        <x-ui.button tag="a" href="{{ route('register') }}" variant="secondary" class="w-full justify-center">Start Free</x-ui.button>
                                    @elseif (auth('web')->check())
                                        <x-ui.button tag="a" href="{{ route('manage.forms.index', ['upgrade' => $slug]) }}" class="w-full justify-center">
                                            Open dashboard to upgrade
                                        </x-ui.button>
                                    @else
                                        <x-ui.button tag="a" href="{{ route('billing.start', ['plan' => $slug]) }}" variant="secondary" class="w-full justify-center">
                                            Create account to choose {{ $plan->name }}
                                        </x-ui.button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="mx-auto w-full max-w-6xl px-4 py-14 md:px-6">
                <div class="rounded-2xl border border-gs-purple-200 bg-[linear-gradient(135deg,#2563eb_0%,#1e40af_100%)] p-6 text-white md:flex md:items-center md:justify-between md:gap-8 md:p-9">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gs-purple-100">Ready to launch</p>
                        <h2 class="mt-2 text-2xl font-semibold leading-tight md:text-3xl">Set up your first form now, capture leads today.</h2>
                        <p class="mt-2 max-w-xl text-sm text-gs-purple-50 md:text-base">Create your workspace, publish a form, and route enquiries to your team in one onboarding flow.</p>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-3 md:mt-0">
                        <a href="{{ route('register') }}" class="inline-flex h-11 items-center rounded border border-white bg-white px-4 text-sm font-semibold text-gs-purple-700 transition hover:bg-gs-purple-50">Create Free Workspace</a>
                        <a href="{{ route('login') }}" class="inline-flex h-11 items-center rounded border border-gs-purple-200 bg-transparent px-4 text-sm font-semibold text-white transition hover:bg-white/10">I already have an account</a>
                    </div>
                </div>
            </section>

            <section class="border-t border-gs-black-100 bg-white">
                <div class="mx-auto flex w-full max-w-6xl flex-col gap-3 px-4 py-6 text-sm text-gs-black-700 md:flex-row md:items-center md:justify-between md:px-6">
                    <p class="text-xs uppercase tracking-[0.12em] text-gs-black-500">GraceSoft Capture</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('help.index') }}" class="font-medium text-gs-purple-700 hover:text-gs-purple-800">Help Guide</a>
                        <a href="{{ route('support.create') }}" class="hover:text-gs-black-900">Contact Support</a>
                        <a href="{{ route('legal.privacy') }}" class="hover:text-gs-black-900">Privacy Policy</a>
                        <a href="{{ route('legal.terms') }}" class="hover:text-gs-black-900">Terms &amp; Conditions</a>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
