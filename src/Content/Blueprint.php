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
use Bonfire\Fields\FieldException;
use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\Section;
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

    private const NAME   = '/^[A-Za-z0-9_-]{1,100}$/';
    private const HANDLE = '/^[A-Za-z][A-Za-z0-9_]{0,99}$/';

    /**
     * @param list<Field>           $fields   every field, in the order the form shows them, whether or not they're in sections
     * @param array<string, string> $seo      the handle of the field that feeds each SEO value (`title`, `description`)
     * @param list<Section>         $sections the groups the form shows the fields in, empty when the blueprint lists plain `fields`
     */
    public function __construct(
        public string $collection,
        public string $handle,
        public string $title,
        public array $fields,
        public array $seo = [],
        public array $sections = [],
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
     * @throws ContentException
     */
    private static function fromFile(string $path, string $collection, string $handle): self
    {
        try {
            $config = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ContentException("Invalid YAML in blueprint {$handle}: {$e->getMessage()}", 0, $e);
        }

        if (! is_array($config)) {
            throw new ContentException("Blueprint {$handle} needs a `fields` list or a `sections` list.");
        }

        if (array_key_exists('fields', $config) && array_key_exists('sections', $config)) {
            throw new ContentException("Blueprint {$handle} has both `fields` and `sections`. Put the fields in the sections, or drop the sections.");
        }

        $fields = $keys = $sections = [];

        if (isset($config['sections'])) {
            if (! is_array($config['sections']) || ! array_is_list($config['sections'])) {
                throw new ContentException("Blueprint {$handle} needs a `sections` list.");
            }

            foreach ($config['sections'] as $position => $definition) {
                $sections[] = self::section($handle, $position + 1, $definition, $fields, $keys);
            }
        } elseif (is_array($config['fields'] ?? null) && array_is_list($config['fields'])) {
            foreach ($config['fields'] as $position => $definition) {
                self::addField($handle, 'Field ' . ($position + 1), $definition, $fields, $keys);
            }
        } else {
            throw new ContentException("Blueprint {$handle} needs a `fields` list or a `sections` list.");
        }

        return new self(
            $collection,
            $handle,
            is_string($config['title'] ?? null) ? $config['title'] : $handle,
            array_values($fields),
            self::seoMapping($handle, $config['seo'] ?? [], $fields),
            $sections,
        );
    }

    /**
     * @param array<string, Field>  $fields every field so far, by handle, which this adds its own to
     * @param array<string, string> $keys   the handle of the field saved under each key so far
     *
     * @throws ContentException when the section can't be used
     */
    private static function section(string $blueprint, int $number, mixed $definition, array &$fields, array &$keys): Section
    {
        $where = "Section {$number} of blueprint {$blueprint}";

        if (! is_array($definition) || ! is_string($definition['title'] ?? null) || trim($definition['title']) === '') {
            throw new ContentException("{$where} needs a `title`.");
        }

        $columns = $definition['columns'] ?? Section::DEFAULT_COLUMNS;

        if (! is_int($columns) || $columns < Section::MIN_COLUMNS || $columns > Section::MAX_COLUMNS) {
            throw new ContentException("{$where} (`{$definition['title']}`): `columns` must be a whole number from " . Section::MIN_COLUMNS . ' to ' . Section::MAX_COLUMNS . '.');
        }

        if (! is_array($definition['fields'] ?? null) || ! array_is_list($definition['fields'])) {
            throw new ContentException("{$where} (`{$definition['title']}`) needs a `fields` list.");
        }

        $own = [];

        foreach ($definition['fields'] as $position => $field) {
            $own[] = self::addField($blueprint, 'Field ' . ($position + 1) . " of section {$number}", $field, $fields, $keys);
        }

        return new Section(
            $definition['title'],
            is_scalar($definition['instructions'] ?? null) ? (string) $definition['instructions'] : null,
            $columns,
            $own,
        );
    }

    /**
     * Reads a field definition and adds the field to the blueprint's, after checking that no earlier field
     * has its handle or saves to its key.
     *
     * @param array<string, Field>  $fields every field so far, by handle
     * @param array<string, string> $keys   the handle of the field saved under each key so far
     *
     * @throws ContentException when the definition can't be used, or repeats a handle or a key
     */
    private static function addField(string $blueprint, string $which, mixed $definition, array &$fields, array &$keys): Field
    {
        $field = self::fromDefinition($blueprint, $which, $definition);

        if (isset($fields[$field->handle])) {
            throw new ContentException("Blueprint {$blueprint} has two fields named `{$field->handle}`.");
        }

        $type = FieldTypes::get($field->type);

        // A type may save its value under another key than the handle, and two fields can't share one.
        if (EntryStorage::inFrontMatter($type)) {
            $key = EntryStorage::key($type, $field);

            if (isset($keys[$key])) {
                throw new ContentException("Blueprint {$blueprint}: fields `{$keys[$key]}` and `{$field->handle}` both save to `{$key}`.");
            }

            $keys[$key] = $field->handle;
        }

        return $fields[$field->handle] = $field;
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
    private static function fromDefinition(string $blueprint, string $which, mixed $definition): Field
    {
        $where = "{$which} of blueprint {$blueprint}";

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

        if (array_key_exists('width', $definition) && $definition['width'] !== 'full') {
            throw new ContentException("{$where} (`{$handle}`): `width` can only be `full`.");
        }

        $options = array_diff_key($definition, array_flip(['handle', 'type', 'label', 'validate', 'instructions', 'width']));

        $field = new Field(
            $handle,
            $definition['type'],
            is_scalar($definition['label'] ?? null) ? (string) $definition['label'] : ucfirst(str_replace('_', ' ', $handle)),
            $validate,
            is_scalar($definition['instructions'] ?? null) ? (string) $definition['instructions'] : null,
            $options,
            ($definition['width'] ?? null) === 'full',
        );

        try {
            FieldTypes::get($field->type)->check($field);
        } catch (FieldException $e) {
            throw new ContentException($e->getMessage(), 0, $e);
        }

        return $field;
    }
}
