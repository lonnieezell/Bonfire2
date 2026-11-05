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

use Bonfire\Content\Fields\FieldTypes;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * The fields an entry form has, from resources/blueprints/collections/<collection>/<blueprint>.yaml.
 */
final readonly class Blueprint
{
    /**
     * The handle of the field that holds the entry's Markdown body instead of a front matter key.
     */
    public const BODY = 'body';

    /**
     * Keys the entry form manages itself, so a field can't use them.
     */
    public const RESERVED = ['id', 'status', 'published_at', 'blueprint'];

    private const NAME   = '/^[A-Za-z0-9_-]{1,100}$/';
    private const HANDLE = '/^[A-Za-z][A-Za-z0-9_]{0,99}$/';

    /**
     * @param list<Field> $fields
     */
    public function __construct(
        public string $collection,
        public string $handle,
        public string $title,
        public array $fields,
    ) {
    }

    /**
     * @throws ContentException when the file is missing or invalid
     */
    public static function load(string $blueprintPath, string $collection, string $handle): self
    {
        $path = rtrim($blueprintPath, '/') . "/collections/{$collection}/{$handle}.yaml";

        // Both names can come from a request.
        if (preg_match(self::NAME, $collection) !== 1 || preg_match(self::NAME, $handle) !== 1 || ! is_file($path)) {
            throw new ContentException("Unknown blueprint: {$collection}/{$handle}");
        }

        return self::fromFile($path, $collection, $handle);
    }

    /**
     * The blueprint of a taxonomy's terms, from resources/blueprints/taxonomies/<taxonomy>.yaml. Without that file,
     * a term has a title and a slug.
     *
     * @throws ContentException when the file is invalid
     */
    public static function loadTerm(string $blueprintPath, string $taxonomy): self
    {
        $path = rtrim($blueprintPath, '/') . "/taxonomies/{$taxonomy}.yaml";

        if (preg_match(self::NAME, $taxonomy) === 1 && is_file($path)) {
            $blueprint = self::fromFile($path, $taxonomy, $taxonomy);

            // A term file is only YAML, so there's nowhere to keep a body.
            if ($blueprint->field(self::BODY) !== null) {
                throw new ContentException("Blueprint {$taxonomy} can't have a `body` field, because a term has no body.");
            }

            return $blueprint;
        }

        return new self($taxonomy, $taxonomy, $taxonomy, [
            new Field('title', 'text', 'Title', 'required|max_length[255]', null, []),
            new Field('slug', 'slug', 'Slug', '', null, []),
        ]);
    }

    /**
     * @throws ContentException
     */
    private static function fromFile(string $path, string $collection, string $handle): self
    {
        try {
            $config = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML in blueprint {$handle}: {$e->getMessage()}", 0, $e);
        }

        if (! is_array($config) || ! is_array($config['fields'] ?? null) || ! array_is_list($config['fields'])) {
            throw new ContentException("Blueprint {$handle} needs a `fields` list.");
        }

        $fields = $keys = [];

        foreach ($config['fields'] as $position => $definition) {
            $field = self::fromDefinition($handle, $position + 1, $definition);

            if (isset($fields[$field->handle])) {
                throw new ContentException("Blueprint {$handle} has two fields named `{$field->handle}`.");
            }

            $type = FieldTypes::get($field->type);

            // A type may save its value under another key than the handle, and two fields can't share one.
            if ($type->storedInFrontMatter()) {
                $key = $type->key($field);

                if (isset($keys[$key])) {
                    throw new ContentException("Blueprint {$handle}: fields `{$keys[$key]}` and `{$field->handle}` both save to `{$key}`.");
                }

                $keys[$key] = $field->handle;
            }

            $fields[$field->handle] = $field;
        }

        return new self($collection, $handle, is_string($config['title'] ?? null) ? $config['title'] : $handle, array_values($fields));
    }

    /**
     * The field with this handle, or null.
     */
    public function field(string $handle): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->handle === $handle) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @throws ContentException when the definition can't be used
     */
    private static function fromDefinition(string $blueprint, int $number, mixed $definition): Field
    {
        $where = "Field {$number} of blueprint {$blueprint}";

        if (! is_array($definition) || ! is_string($definition['handle'] ?? null) || preg_match(self::HANDLE, $definition['handle']) !== 1) {
            throw new ContentException("{$where} needs a `handle` of letters, numbers and _.");
        }

        $handle = $definition['handle'];

        if (in_array($handle, self::RESERVED, true)) {
            throw new ContentException("{$where}: `{$handle}` is managed by the entry form, so a field can't use it.");
        }

        if (! is_string($definition['type'] ?? null) || ! FieldTypes::has($definition['type'])) {
            throw new ContentException("{$where} (`{$handle}`) needs a `type`: " . implode(', ', FieldTypes::names()) . '.');
        }

        $validate = $definition['validate'] ?? '';

        if (! is_string($validate) && (! is_array($validate) || ! array_is_list($validate) || array_filter($validate, is_string(...)) !== $validate)) {
            throw new ContentException("{$where} (`{$handle}`): `validate` must be a string of rules or a list of them.");
        }

        $options = array_diff_key($definition, array_flip(['handle', 'type', 'label', 'validate', 'instructions']));

        $field = new Field(
            $handle,
            $definition['type'],
            is_scalar($definition['label'] ?? null) ? (string) $definition['label'] : ucfirst(str_replace('_', ' ', $handle)),
            $validate,
            is_scalar($definition['instructions'] ?? null) ? (string) $definition['instructions'] : null,
            $options,
        );

        FieldTypes::get($field->type)->check($field);

        return $field;
    }
}
