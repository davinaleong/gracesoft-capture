@extends('layouts.auth', [
    'title' => 'Product Updates | GraceSoft Capture',
    'metaDescription' => 'Read the latest GraceSoft Capture product announcements, feature releases, and improvements.',
    'canonicalUrl' => route('blog.index'),
    'metaRobots' => 'index,follow',
])

@section('content')
    <x-ui.card class="space-y-5">
        <div class="space-y-2">
            <p class="inline-flex rounded-full border border-gs-purple-200 bg-gs-purple-50 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-gs-purple-700">
                GraceSoft Updates
            </p>
            <h1 class="text-3xl font-bold text-gs-black-950">Product blog</h1>
            <p class="text-gs-black-700">
                New features, fixes, and product improvements for GraceSoft Capture.
            </p>
        </div>

        <div class="space-y-3">
            @forelse ($posts as $post)
                <article class="rounded-xl border border-gs-black-100 bg-white p-4">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gs-black-600">
                        <span>{{ $post['published_at']->format('M j, Y') }}</span>
                        <span aria-hidden="true">•</span>
                        <span>{{ $post['author'] }}</span>
                    </div>

                    <h2 class="mt-2 text-xl font-semibold text-gs-black-950">
                        <a href="{{ route('blog.show', $post['slug']) }}" class="hover:text-gs-purple-700">
                            {{ $post['title'] }}
                        </a>
                    </h2>

                    <p class="mt-2 text-sm text-gs-black-700">{{ $post['excerpt'] }}</p>

                    <a href="{{ route('blog.show', $post['slug']) }}" class="mt-3 inline-flex text-sm font-semibold text-gs-purple-700 hover:text-gs-purple-800">
                        Read update →
                    </a>
                </article>
            @empty
                <x-ui.empty-state
                    title="No updates published yet"
                    body="Create markdown files in resources/blog to publish product updates."
                />
            @endforelse
        </div>
    </x-ui.card>
@endsection
