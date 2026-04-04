@extends('layouts.auth', [
    'title' => $post['title'] . ' | GraceSoft Updates',
    'metaDescription' => $post['excerpt'],
    'canonicalUrl' => route('blog.show', $post['slug']),
    'metaRobots' => 'index,follow',
])

@section('content')
    @php
        $shareUrl = route('blog.show', $post['slug']);
        $shareText = $post['title'] . ' | GraceSoft Capture';

        $encodedUrl = urlencode($shareUrl);
        $encodedText = urlencode($shareText);

        $shareLinks = [
            'X (Twitter)' => "https://twitter.com/intent/tweet?text={$encodedText}&url={$encodedUrl}",
            'Facebook' => "https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}",
            'LinkedIn' => "https://www.linkedin.com/sharing/share-offsite/?url={$encodedUrl}",
            'WhatsApp' => "https://api.whatsapp.com/send?text={$encodedText}%20{$encodedUrl}",
            'Email' => 'mailto:?subject=' . urlencode($shareText) . '&body=' . urlencode($shareText . "\n\n" . $shareUrl),
        ];
    @endphp

    <x-ui.card class="space-y-5">
        <a href="{{ route('blog.index') }}" class="inline-flex text-sm font-semibold text-gs-purple-700 hover:text-gs-purple-800">← Back to updates</a>

        <header class="space-y-2">
            <div class="flex flex-wrap items-center gap-2 text-xs text-gs-black-600">
                <span>{{ $post['published_at']->format('F j, Y') }}</span>
                <span aria-hidden="true">•</span>
                <span>{{ $post['author'] }}</span>
            </div>
            <h1 class="text-3xl font-bold text-gs-black-950">{{ $post['title'] }}</h1>
            <p class="text-gs-black-700">{{ $post['excerpt'] }}</p>
        </header>

        <article class="blog-content rounded-xl border border-gs-black-100 bg-white p-5">{!! $post['html'] !!}</article>

        <section class="rounded-xl border border-gs-black-100 bg-gs-black-50 p-4">
            <h2 class="text-base font-semibold text-gs-black-900">Share this update</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($shareLinks as $label => $url)
                    <a
                        href="{{ $url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center rounded border border-gs-black-200 bg-white px-3 py-1.5 text-sm font-semibold text-gs-black-800 hover:border-gs-purple-300 hover:text-gs-purple-700"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>
    </x-ui.card>
@endsection
