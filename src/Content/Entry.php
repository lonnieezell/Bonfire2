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

use Bonfire\Content\Fields\EntryStorage;
use Bonfire\Fields\Field;
use CodeIgniter\I18n\Time;
use RuntimeException;

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
     * A front matter value, as the file holds it. The rows of a `list` field are cast by their sub-fields' types.
     */
    public function field(string $name, mixed $default = null): mixed
    {
        $value = $this->data[$name] ?? null;

        // Only a list with maps in it can be a list field's rows, so nothing else costs a read of the blueprint.
        if (is_array($value) && array_is_list($value) && array_filter($value, is_array(...)) !== []) {
            $field = $this->listField($name);
            $value = $field === null ? $value : EntryStorage::cast($value, $field);
        }

        return $value ?? $default;
    }

    /**
     * The `list` field with this handle, or null. The blueprint's file may be gone or broken by now, as the index
     * outlives it, and then the rows are returned as the file holds them.
     */
    private function listField(string $name): ?Field
    {
        $field = $this->ownBlueprint()?->field($name);

        return $field?->type === 'list' ? $field : null;
    }

    /**
     * The entry's blueprint, or null when it has none or its file is gone or broken by now, as the index outlives it.
     */
    private function ownBlueprint(): ?Blueprint
    {
        if ($this->blueprint === null) {
            return null;
        }

        try {
            return Blueprint::load(config('Content')->blueprintPath, $this->collection, $this->blueprint);
        } catch (ContentException $e) {
            log_message('error', "Content: blueprint {$this->collection}/{$this->blueprint}: {$e->getMessage()}");

            return null;
        }
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
     * The blocks of a `blocks` field, in the order the file lists them. A field the entry doesn't have, or that isn't
     * a list, gives none, and so does an item that isn't a map with a `type`.
     */
    public function blocks(string $name): BlockList
    {
        $items = $this->data[$name] ?? null;
        $items = is_array($items) && array_is_list($items) ? $items : [];

        $blueprints = [];
        $blocks     = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['type'] ?? null)) {
                continue;
            }

            $type = $item['type'];

            if (! array_key_exists($type, $blueprints)) {
                $blueprints[$type] = $this->blockBlueprint($type);
            }

            unset($item['type']);
            $blocks[] = new Block($type, $item, $blueprints[$type], $this);
        }

        return new BlockList($blocks);
    }

    /**
     * The first block of a `blocks` field, or null when it has none.
     */
    public function block(string $name): ?Block
    {
        return $this->blocks($name)->first();
    }

    /**
     * Every block of a `blocks` field through its partial, in order.
     *
     * @throws ContentException when a partial doesn't exist, except in production, where it is logged and gives nothing
     */
    public function renderBlocks(string $name): string
    {
        return $this->blocks($name)->render();
    }

    /**
     * The blueprint of a block type. Its file may be gone or broken by now, as the index outlives it, and then
     * the block's values are returned as the file holds them.
     */
    private function blockBlueprint(string $type): ?Blueprint
    {
        try {
            return Blueprint::loadBlock(config('Content')->blueprintPath, $type);
        } catch (ContentException $e) {
            log_message('error', 'Content: block type {type}: {message}', ['type' => $type, 'message' => $e->getMessage()]);

            return null;
        }
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
        return $this->ownBlueprint()?->seo ?? [];
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
        preg_match_all('~<p(?:\s[^>]*)?>(.*?)</p>~s', $this->content(components: false), $matches);

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
     * The Markdown body as HTML, with any `<x-…>` View Components expanded. Pass `components: false` to leave them
     * as written. Raw HTML in the body passes through, apart from the tags GitHub's rules disallow, so only
     * render bodies you trust.
     *
     * @throws RuntimeException when a component doesn't exist, except in production, where it is logged and removed
     */
    public function content(bool $components = true): string
    {
        return Markdown::toHtml($this->body, $components);
    }
}
