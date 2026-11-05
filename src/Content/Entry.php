<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content;

use CodeIgniter\I18n\Time;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * An indexed entry: its columns, its front matter and its raw Markdown body.
 */
final readonly class Entry
{
    /**
     * @param array<array-key, mixed> $data the front matter
     */
    public function __construct(
        public string $id,
        public string $collection,
        public ?string $blueprint,
        public string $slug,
        public ?string $uri,
        public string $status,
        public ?string $publishedAt,
        public string $title,
        public string $body,
        public array $data,
    ) {
    }

    /**
     * @param array<string, mixed> $row a content_entries row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['collection'],
            $row['blueprint'],
            $row['slug'],
            $row['uri'],
            $row['status'],
            $row['published_at'],
            $row['title'],
            $row['body'],
            json_decode($row['data'], true, flags: JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Whether the entry is served: it has the `published` status, and its `published_at` has passed or it has none.
     * The same rule as `published()` on a query.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && ($this->publishedAt === null || $this->publishedAt <= Time::now()->toDateTimeString());
    }

    /**
     * A front matter value.
     */
    public function field(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }

    /**
     * The entries an `entries` field links to, in the order the file lists them. A target that is missing or in the
     * trash is skipped, and so is a field that isn't a list of ids. Drafts and scheduled entries are returned,
     * so check `$status` before showing one.
     *
     * @return list<Entry>
     */
    public function entries(string $name): array
    {
        $ids = $this->data[$name] ?? null;

        return is_array($ids) ? content()->findMany(array_values(array_filter($ids, is_string(...)))) : [];
    }

    /**
     * The values for the page's SEO tags. The blueprint maps the fields that feed the title and the description,
     * and a value left unmapped or blank comes from `title` and the body's first paragraph as plain text.
     * The canonical URL is the entry's `uri` on the site's base URL, and is null for an entry without one.
     * Values are plain text: escape them when you print them.
     *
     * @return array{title: string, description: string, canonical: ?string, og: array<string, string>}
     */
    public function seo(): array
    {
        $map = $this->seoMapping();

        $title       = $this->mappedText($map['title'] ?? null) ?? $this->title;
        $description = $this->mappedText($map['description'] ?? null) ?? $this->firstParagraph();
        $canonical   = $this->uri === null ? null : base_url($this->uri);

        $og = array_filter(
            ['og:title' => $title, 'og:description' => $description, 'og:type' => 'website', 'og:url' => $canonical],
            static fn (?string $value): bool => $value !== null && $value !== '',
        );

        return ['title' => $title, 'description' => $description, 'canonical' => $canonical, 'og' => $og];
    }

    /**
     * The blueprint's SEO mapping. Its file may be gone or broken by now, as the index outlives it.
     *
     * @return array<string, string>
     */
    private function seoMapping(): array
    {
        if ($this->blueprint === null) {
            return [];
        }

        try {
            return Blueprint::load(config('Content')->blueprintPath, $this->collection, $this->blueprint)->seo;
        } catch (ContentException $e) {
            log_message('error', "Content: blueprint {$this->collection}/{$this->blueprint}: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * The text of a front matter value, or null when it's blank or not text or a number. A toggle is neither.
     */
    private function mappedText(?string $name): ?string
    {
        $value = $name === null ? null : $this->field($name);
        $text  = is_string($value) || is_int($value) || is_float($value) ? trim((string) $value) : '';

        return $text === '' ? null : $text;
    }

    /**
     * The body's first paragraph with its markup removed and its whitespace collapsed.
     */
    private function firstParagraph(): string
    {
        preg_match_all('~<p(?:\s[^>]*)?>(.*?)</p>~s', $this->content(), $matches);

        foreach ($matches[1] as $paragraph) {
            $text = html_entity_decode(strip_tags($paragraph), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim((string) preg_replace('/\s+/u', ' ', $text));

            // A paragraph that is only an image has no text to describe the page with.
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    /**
     * The Markdown body as HTML. Raw HTML in the body passes through, apart from the tags
     * GitHub's rules disallow, so only render bodies you trust.
     */
    public function content(): string
    {
        return (new GithubFlavoredMarkdownConverter(['allow_unsafe_links' => false]))->convert($this->body)->getContent();
    }
}
