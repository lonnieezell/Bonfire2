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

use Bonfire\Fields\Field;
use Bonfire\Fields\FieldTypes;
use DateTimeInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * A set of site-wide values, from content/globals/<handle>.yaml. It's read from the file, with no index.
 */
final readonly class GlobalSet
{
    /**
     * @param array<array-key, mixed> $data      the file's values
     * @param ?Blueprint              $blueprint what describes the values, if the set has one
     * @param string                  $hash      of the file the values were read from, for a form to catch a change since
     */
    public function __construct(
        public string $handle,
        private array $data,
        public ?Blueprint $blueprint,
        public string $hash,
    ) {
    }

    /**
     * @throws ContentException when the file is missing or invalid
     */
    public static function load(string $contentRoot, string $blueprintPath, string $handle): self
    {
        $path = rtrim($contentRoot, '/') . "/globals/{$handle}.yaml";

        // The handle may come from a template or a request.
        if (preg_match('/^[A-Za-z0-9_-]{1,100}$/', $handle) !== 1 || ! is_file($path)) {
            throw new ContentException("Unknown globals: {$handle}");
        }

        // One read, so the hash is of the text the values came from.
        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new ContentException("The globals {$handle} could not be read.");
        }

        try {
            $data = Yaml::parse($raw, Yaml::PARSE_DATETIME);
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML in globals {$handle}: {$e->getMessage()}", 0, $e);
        }

        // An empty file is a set with nothing in it.
        $data ??= [];

        if (! is_array($data) || ($data !== [] && array_is_list($data))) {
            throw new ContentException("The globals {$handle} must be a YAML mapping.");
        }

        // YAML turns unquoted dates into objects, and a value should be text like an entry's front matter.
        array_walk_recursive($data, static function (mixed &$value): void {
            if ($value instanceof DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }
        });

        return new self($handle, $data, Blueprint::loadGlobal($blueprintPath, $handle), hash('sha256', $raw));
    }

    /**
     * Every set with a valid file. One that can't be loaded is logged and left out.
     *
     * @return list<self>
     */
    public static function all(string $contentRoot, string $blueprintPath): array
    {
        $sets = [];

        foreach (glob(rtrim($contentRoot, '/') . '/globals/*.yaml') ?: [] as $file) {
            try {
                $sets[] = self::load($contentRoot, $blueprintPath, basename($file, '.yaml'));
            } catch (ContentException $e) {
                log_message('error', 'Content: globals {set}: {message}', ['set' => basename($file), 'message' => $e->getMessage()]);
            }
        }

        return $sets;
    }

    /**
     * The file's values as the admin form edits them: dates as text, and keys the blueprint doesn't list as they are.
     *
     * @return array<array-key, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * A value, cast by the type of the blueprint field that holds it. A value the file leaves out or empty,
     * or that doesn't fit an integer or date, gives the default.
     */
    public function field(string $name, mixed $default = null): mixed
    {
        $value = $this->data[$name] ?? null;

        if ($value !== null) {
            foreach ($this->blueprint->fields ?? [] as $field) {
                if ($field->handle === $name) {
                    $value = self::cast($value, $field);

                    break;
                }
            }
        }

        return $value === null || $value === '' ? $default : $value;
    }

    /**
     * Casts a value as its field's type reads it from a file: a toggle is a bool, an integer an int, and a date
     * or datetime text. Null when it doesn't fit.
     */
    private static function cast(mixed $value, Field $field): mixed
    {
        $text = FieldTypes::get($field->type)->fromStored($value, $field);

        return match ($field->type) {
            'toggle'           => $text === '1',
            'integer'          => $text === '' ? null : (int) $text,
            'date', 'datetime' => $text === '' ? null : $text,
            default            => $value,
        };
    }
}
