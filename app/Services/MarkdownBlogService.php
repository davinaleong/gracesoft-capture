<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

class MarkdownBlogService
{
    public function all(): Collection
    {
        $directory = resource_path('blog');

        if (! File::isDirectory($directory)) {
            return collect();
        }

        return collect(File::files($directory))
            ->filter(fn (SplFileInfo $file): bool => strtolower($file->getExtension()) === 'md')
            ->map(fn (SplFileInfo $file): ?array => $this->parsePostFile($file))
            ->filter(fn (?array $post): bool => is_array($post))
            ->sortByDesc('published_at')
            ->values();
    }

    public function findBySlug(string $slug): ?array
    {
        $normalizedSlug = Str::slug($slug);

        return $this->all()->first(fn (array $post): bool => $post['slug'] === $normalizedSlug);
    }

    private function parsePostFile(SplFileInfo $file): ?array
    {
        $raw = File::get($file->getPathname());
        [$frontMatter, $markdown] = $this->splitFrontMatter($raw);

        [$defaultSlug, $filenameDate] = $this->deriveDefaultsFromFilename($file->getFilename());

        $title = trim((string) ($frontMatter['title'] ?? ''));
        if ($title === '') {
            $title = $this->extractHeading($markdown) ?? Str::headline($defaultSlug);
        }

        $slug = Str::slug((string) ($frontMatter['slug'] ?? $defaultSlug));
        if ($slug === '') {
            return null;
        }

        $publishedAt = $this->resolvePublishedAt(
            (string) ($frontMatter['date'] ?? ''),
            $filenameDate,
            $file->getMTime(),
        );

        $excerpt = trim((string) ($frontMatter['excerpt'] ?? ''));
        if ($excerpt === '') {
            $excerpt = $this->buildExcerpt($markdown);
        }

        $html = (string) Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return [
            'slug' => $slug,
            'title' => $title,
            'author' => trim((string) ($frontMatter['author'] ?? 'GraceSoft Team')),
            'excerpt' => $excerpt,
            'published_at' => $publishedAt,
            'markdown' => $markdown,
            'html' => $html,
        ];
    }

    private function splitFrontMatter(string $raw): array
    {
        if (! preg_match('/\A---\R(.*?)\R---\R?(.*)\z/s', $raw, $matches)) {
            return [[], $raw];
        }

        $frontMatter = [];

        foreach (preg_split('/\R/', trim($matches[1])) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! preg_match('/^([A-Za-z0-9_-]+)\s*:\s*(.+)$/', $line, $parts)) {
                continue;
            }

            $key = strtolower(trim($parts[1]));
            $value = trim($parts[2]);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            $frontMatter[$key] = $value;
        }

        return [$frontMatter, (string) $matches[2]];
    }

    private function deriveDefaultsFromFilename(string $filename): array
    {
        $baseName = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)$/', $baseName, $matches)) {
            return [$matches[2], $matches[1]];
        }

        return [$baseName, null];
    }

    private function extractHeading(string $markdown): ?string
    {
        if (preg_match('/^\s*#\s+(.+)$/m', $markdown, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function resolvePublishedAt(string $metaDate, ?string $filenameDate, int $fileMTime): CarbonImmutable
    {
        $candidates = array_filter([$metaDate, $filenameDate]);

        foreach ($candidates as $candidate) {
            try {
                return CarbonImmutable::parse($candidate)->startOfDay();
            } catch (\Throwable) {
                // Ignore invalid metadata dates and continue fallback chain.
            }
        }

        return CarbonImmutable::createFromTimestamp($fileMTime);
    }

    private function buildExcerpt(string $markdown): string
    {
        $plain = trim(strip_tags((string) Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ])));

        if ($plain === '') {
            return 'Read the latest GraceSoft Capture update.';
        }

        return Str::limit($plain, 180);
    }
}
