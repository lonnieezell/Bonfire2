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

    /**
     * The SEO values a blueprint can map a field to.
     */
    private const SEO = ['title', 'description'];

    public const NAME    = '/^[A-Za-z0-9_-]{1,100}$/';
    private const HANDLE = '/^[A-Za-z][A-Za-z0-9_]{0,99}$/';

    /**
     * The field types a block can't have: a slug names a file, terms are indexed, and blocks can't hold blocks.
     */
    private const NOT_IN_BLOCKS = ['slug', 'terms', 'blocks'];

    /**
     * @param list<Field>           $fields
     * @param array<string, string> $seo    the handle of the field that feeds each SEO value (`title`, `description`)
     * @param ?string               $icon   the icon of a block type, for the editor to show
     */
    public function __construct(
        public string $collection,
        public string $handle,
        public string $title,
        public array $fields,
        public array $seo = [],
        public ?string $icon = null,
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
     * The blueprint of a global set's values, from resources/blueprints/globals/<handle>.yaml, or null without that file.
     *
     * @throws ContentException when the file is invalid
     */
    public static function loadGlobal(string $blueprintPath, string $handle): ?self
    {
        $path = rtrim($blueprintPath, '/') . "/globals/{$handle}.yaml";

        if (preg_match(self::NAME, $handle) !== 1 || ! is_file($path)) {
            return null;
        }

        $blueprint = self::fromFile($path, $handle, $handle);

        // A global set is only YAML, so there's nowhere to keep a body.
        if ($blueprint->field(self::BODY) !== null) {
            throw new ContentException("Blueprint {$handle} can't have a `body` field, because a global set has no body.");
        }

        return $blueprint;
    }

    /**
     * The blueprint of a block type, from resources/blueprints/blocks/<type>.yaml. A block can't have a `slug`, `terms`
     * or `blocks` field, or one with the handle `type`, which holds the block's type in the front matter.
     *
     * @throws ContentException when the file is missing or invalid
     */
    public static function loadBlock(string $blueprintPath, string $type): self
    {
        $path = rtrim($blueprintPath, '/') . "/blocks/{$type}.yaml";

        // The type comes from front matter or a request.
        if (preg_match(self::NAME, $type) !== 1 || ! is_file($path)) {
            throw new ContentException("Unknown block type: {$type}. Its blueprint is resources/blueprints/blocks/{$type}.yaml.");
        }

        return self::fromFile($path, 'blocks', $type, true);
    }

    /**
     * @throws ContentException
     */
    private static function fromFile(string $path, string $collection, string $handle, bool $block = false): self
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
            $field = self::fromDefinition($handle, $position + 1, $definition, $block);

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

        return new self(
            $collection,
            $handle,
            is_string($config['title'] ?? null) ? $config['title'] : $handle,
            array_values($fields),
            self::seoMapping($handle, $config['seo'] ?? [], $fields),
            is_string($config['icon'] ?? null) && $config['icon'] !== '' ? $config['icon'] : null,
        );
    }

    /**
     * @param array<string, Field> $fields
     *
     * @return array<string, string>
     *
     * @throws ContentException when the mapping names something that isn't an SEO value or a field
     */
    private static function seoMapping(string $handle, mixed $seo, array $fields): array
    {
        $allowed = implode(' and ', self::SEO);

        if (! is_array($seo)) {
            throw new ContentException("Blueprint {$handle}: `seo` must map {$allowed} to field handles.");
        }

        foreach ($seo as $key => $field) {
            if (! in_array($key, self::SEO, true)) {
                throw new ContentException("Blueprint {$handle}: `seo` can map {$allowed}, not `{$key}`.");
            }

            if (! is_string($field) || ! isset($fields[$field])) {
                throw new ContentException("Blueprint {$handle}: `seo.{$key}` needs the handle of one of its fields" . (is_string($field) ? ", not `{$field}`." : '.'));
            }
        }

        return $seo;
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
    private static function fromDefinition(string $blueprint, int $number, mixed $definition, bool $block): Field
    {
        $where = $block ? "Field {$number} of block type {$blueprint}" : "Field {$number} of blueprint {$blueprint}";

        if (! is_array($definition) || ! is_string($definition['handle'] ?? null) || preg_match(self::HANDLE, $definition['handle']) !== 1) {
            throw new ContentException("{$where} needs a `handle` of letters, numbers and _.");
        }

        $handle = $definition['handle'];

        if ($block && $handle === 'type') {
            throw new ContentException("{$where}: a block's `type` is kept in a key named `type`, so a field can't use that handle.");
        }

        if (in_array($handle, self::RESERVED, true)) {
            throw new ContentException("{$where}: `{$handle}` is managed by the entry form, so a field can't use it.");
        }

        if (! is_string($definition['type'] ?? null) || ! FieldTypes::has($definition['type'])) {
            throw new ContentException("{$where} (`{$handle}`) needs a `type`: " . implode(', ', FieldTypes::names()) . '.');
        }

        if ($block && in_array($definition['type'], self::NOT_IN_BLOCKS, true)) {
            throw new ContentException("{$where} (`{$handle}`): a block can't have a `{$definition['type']}` field.");
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
