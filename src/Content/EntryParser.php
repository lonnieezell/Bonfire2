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

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use JsonException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Turns an entry file (Markdown with YAML front matter) into its index columns.
 */
final class EntryParser
{
    private const DATETIME = 'Y-m-d H:i:s';

    /**
     * @param string $raw      the file's contents
     * @param string $filename the file's name, which holds the slug and, in a dated collection, the date
     *
     * @return array<string, int|string|null> the index columns that come from the file
     *
     * @throws ContentException when the file can't be indexed
     */
    public static function parse(Collection $collection, string $raw, string $filename): array
    {
        if (! mb_check_encoding($raw, 'UTF-8')) {
            throw new ContentException('The file is not valid UTF-8.');
        }

        [$frontMatter, $body] = self::split($raw);

        $id = $frontMatter['id'] ?? null;
        if (! ContentFiles::isUuid($id)) {
            throw new ContentException('The front matter needs an `id` that is a UUID.');
        }

        [$slug, $fileDate] = self::parseFilename($collection, $filename);

        $status = $frontMatter['status'] ?? 'published';
        if (! in_array($status, ['draft', 'published'], true)) {
            throw new ContentException('`status` must be draft or published.');
        }

        $title       = is_scalar($frontMatter['title'] ?? null) ? (string) $frontMatter['title'] : $slug;
        $blueprint   = $frontMatter['blueprint'] ?? $collection->blueprints[0] ?? null;
        $blueprint   = is_string($blueprint) ? $blueprint : null;
        $publishedAt = isset($frontMatter['published_at']) ? self::parseDate($frontMatter['published_at'], '`published_at`') : $fileDate;

        // published() takes an entry with no date as live, so a mistyped date mustn't publish it.
        if ($collection->dated && $publishedAt === null) {
            throw new ContentException('The filename needs to start with a date (2026-10-27.my-post.md), or the front matter needs a `published_at`.');
        }

        // In a structured collection the tree decides the URI, so the indexer sets it.
        $uri = $collection->structure ? null : $collection->uriFor($slug);

        self::assertFits('slug', $slug, 255);
        self::assertFits('uri', (string) $uri, 255);
        self::assertFits('title', $title, 255);
        self::assertFits('blueprint', (string) $blueprint, 100);

        return [
            'id'           => strtolower($id),
            'collection'   => $collection->handle,
            'blueprint'    => $blueprint,
            'slug'         => $slug,
            'uri'          => $uri,
            'parent_id'    => null,
            'sort'         => null,
            'status'       => $status,
            'published_at' => $publishedAt,
            'title'        => $title,
            'data'         => self::encode($frontMatter),
            'body'         => $body,
            'hash'         => hash('sha256', $raw),
        ];
    }

    /**
     * @throws ContentException when a value is too long for its index column
     */
    private static function assertFits(string $field, string $value, int $length): void
    {
        if (mb_strlen($value) > $length) {
            throw new ContentException("`{$field}` is longer than {$length} characters.");
        }
    }

    /**
     * @return array{array<array-key, mixed>, string} the front matter and the raw body
     */
    private static function split(string $raw): array
    {
        if (preg_match('/\A---[ \t]*\R(.*?)^---[ \t]*(?:\R|\z)/ms', $raw, $matches) !== 1) {
            throw new ContentException('The file has no front matter block.');
        }

        try {
            $frontMatter = Yaml::parse($matches[1], Yaml::PARSE_DATETIME);
        } catch (ParseException $e) {
            throw new ContentException("Malformed front matter: {$e->getMessage()}", 0, $e);
        }

        if (! is_array($frontMatter)) {
            throw new ContentException('The front matter must be a YAML mapping.');
        }

        // YAML turns unquoted dates into objects, which JSON can't hold as dates.
        array_walk_recursive($frontMatter, static function (mixed &$value): void {
            if ($value instanceof DateTimeInterface) {
                $value = self::toAppTime($value);
            }
        });

        return [$frontMatter, substr($raw, strlen($matches[0]))];
    }

    /**
     * @return array{string, string|null} the slug and the date from the filename
     */
    private static function parseFilename(Collection $collection, string $filename): array
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        if (! $collection->dated || preg_match('/^(\d{4}-\d{2}-\d{2})\.(.+)$/', $name, $matches) !== 1) {
            return [$name, null];
        }

        return [$matches[2], self::parseDate($matches[1], "The filename's date")];
    }

    /**
     * @param mixed  $value a date from the file. An unquoted one from the front matter has been through toAppTime().
     * @param string $label what to call the date in an error
     */
    private static function parseDate(mixed $value, string $label): string
    {
        $pattern = '/^(\d{4})-(\d{2})-(\d{2})(?:[ T]\d{2}:\d{2}(?::\d{2})?)?(?: ?(?:Z|[+-]\d{2}:?\d{2}))?$/';

        if (! is_string($value) || preg_match($pattern, $value, $parts) !== 1 || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new ContentException("{$label} is not a date.");
        }

        try {
            return self::toAppTime(new DateTimeImmutable($value));
        } catch (Exception) {
            throw new ContentException("{$label} is not a date.");
        }
    }

    /**
     * YAML hands over a date with no offset as UTC, though it means app time.
     */
    public static function toAppTime(DateTimeInterface $date): string
    {
        if ($date->getTimezone()->getName() !== 'UTC') {
            $date = DateTimeImmutable::createFromInterface($date)->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }

        return $date->format(self::DATETIME);
    }

    /**
     * @param array<array-key, mixed> $frontMatter
     */
    private static function encode(array $frontMatter): string
    {
        try {
            return json_encode($frontMatter, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new ContentException("The front matter can't be stored: {$e->getMessage()}", 0, $e);
        }
    }
}
