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

use JsonException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Turns a term file (YAML) into its index columns.
 */
final class TermParser
{
    /**
     * @param string $raw      the file's contents
     * @param string $filename the file's name, which holds the slug
     *
     * @return array<string, string> the index columns that come from the file
     *
     * @throws ContentException when the file can't be indexed
     */
    public static function parse(Taxonomy $taxonomy, string $raw, string $filename): array
    {
        if (! mb_check_encoding($raw, 'UTF-8')) {
            throw new ContentException('The file is not valid UTF-8.');
        }

        $data = self::read($raw);
        $id   = $data['id'] ?? null;

        if (! ContentFiles::isUuid($id)) {
            throw new ContentException('The file needs an `id` that is a UUID.');
        }

        $slug = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match(Taxonomy::SLUG, $slug) !== 1) {
            throw new ContentException('The file name needs to be a slug of letters, numbers, - and _.');
        }

        $title = is_scalar($data['title'] ?? null) ? (string) $data['title'] : $slug;

        if (mb_strlen($title) > 255) {
            throw new ContentException('`title` is longer than 255 characters.');
        }

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new ContentException("The file can't be stored: {$e->getMessage()}", 0, $e);
        }

        return [
            'id'       => strtolower($id),
            'taxonomy' => $taxonomy->handle,
            'slug'     => $slug,
            'title'    => $title,
            'data'     => $json,
            'hash'     => hash('sha256', $raw),
        ];
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws ContentException
     */
    private static function read(string $raw): array
    {
        try {
            $data = Yaml::parse($raw);
        } catch (ParseException $e) {
            throw new ContentException("Malformed YAML: {$e->getMessage()}", 0, $e);
        }

        if (! is_array($data)) {
            throw new ContentException('The file must be a YAML mapping.');
        }

        return $data;
    }
}
