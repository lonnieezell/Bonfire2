<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Fields;

use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\Field;

/**
 * A list of blocks, each of a type that has a blueprint in resources/blueprints/blocks. `blocks` lists the types the
 * field allows and is every type when it's left out. `max_items` and `min_items` bound how many blocks there are.
 * The front matter holds a list of maps, each with a reserved `type` key. The form value is that list as JSON,
 * with every value as text like any other form value, or the empty string for no blocks.
 * Read the blocks back with `$entry->blocks('handle')`.
 */
final class BlocksField extends FieldType
{
    public function partial(): string
    {
        return 'blocks';
    }

    /**
     * Every type the field allows has to have a blueprint, and there has to be at least one.
     */
    public function check(Field $field): void
    {
        $types = self::types($field);

        if ($types === []) {
            throw new ContentException("Field `{$field->handle}` has no block types. Add a file to resources/blueprints/blocks, or list the types it allows in `blocks`.");
        }

        foreach ($types as $type) {
            try {
                Blueprint::loadBlock(config('Content')->blueprintPath, $type);
            } catch (ContentException $e) {
                throw new ContentException("Field `{$field->handle}`: {$e->getMessage()}", 0, $e);
            }
        }
    }

    /**
     * Takes the posted blocks, a list of maps, in the order they were posted. A value is tidied by the type of the
     * block field that holds it, and an item that isn't a map is dropped.
     */
    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        $items = array_values(array_filter(is_array($input) ? $input : [], is_array(...)));

        $types      = array_map(static fn (array $item): string => is_string($item['type'] ?? null) ? trim($item['type']) : '', $items);
        $blueprints = self::blueprintsFor($types);
        $blocks     = [];

        foreach ($items as $index => $item) {
            $type  = $types[$index];
            $block = ['type' => $type];

            foreach ($blueprints[$type]->fields ?? [] as $blockField) {
                $block[$blockField->handle] = FieldTypes::get($blockField->type)->prepare($item[$blockField->handle] ?? null, $blockField, ['fields' => $item]);
            }

            $blocks[] = $block;
        }

        return ChoiceList::encode($blocks);
    }

    /**
     * There can be no more blocks than `max_items` and no fewer than `min_items`, each block is of an allowed type, and
     * each of its fields passes its own rules. The message names the first problem and the block's position.
     */
    public function rules(Field $field): array|string
    {
        return $this->withRules($field, function (mixed $value, mixed $data, ?string &$error = null) use ($field): bool {
            $problems = $this->itemErrors(is_string($value) ? $value : '', $field);

            if ($problems === []) {
                return true;
            }

            $position = (int) strtok(array_key_first($problems), '.');
            $error    = $position === 0 ? reset($problems) : lang('Content.blockError', [$position, self::titleOf($value, $position), reset($problems)]);

            return false;
        });
    }

    public function itemErrors(string $input, Field $field): array
    {
        $blocks = self::decode($input);
        $errors = [];
        $max    = self::bound($field, 'max_items');
        $min    = self::bound($field, 'min_items');

        if ($max !== null && count($blocks) > $max) {
            $errors[''] = lang('Content.blocksTooMany', [$max]);
        } elseif ($min !== null && count($blocks) < $min) {
            $errors[''] = lang('Content.blocksTooFew', [$min]);
        }

        if ($blocks === []) {
            return $errors;
        }

        $allowed    = self::types($field);
        $blueprints = self::blueprintsFor(array_column($blocks, 'type'));

        foreach ($blocks as $index => $block) {
            $position = $index + 1;
            $type     = $block['type'];

            if (! in_array($type, $allowed, true) || ! isset($blueprints[$type])) {
                $errors[(string) $position] = lang('Content.blockTypeInvalid', [$type]);

                continue;
            }

            foreach ($blueprints[$type]->fields as $blockField) {
                $rules = FieldTypes::get($blockField->type)->rules($blockField);

                if ($rules === '' || $rules === []) {
                    continue;
                }

                $validation = single_service('validation')->setRules(['value' => ['label' => $blockField->label, 'rules' => $rules]]);
                $validation->run(['value' => $block[$blockField->handle] ?? '']);

                if (($error = $validation->getError('value')) !== '') {
                    $errors["{$position}.{$blockField->handle}"] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * Each block's values are written by the type of the block field that holds them. A value left empty adds no key.
     *
     * @return list<array<string, mixed>>
     */
    public function toFrontMatter(string $input, Field $field): array
    {
        $blocks     = self::decode($input);
        $blueprints = self::blueprintsFor(array_column($blocks, 'type'));
        $stored     = [];

        foreach ($blocks as $block) {
            $map = ['type' => $block['type']];

            foreach ($blueprints[$block['type']]->fields ?? [] as $blockField) {
                $type  = FieldTypes::get($blockField->type);
                $value = $type->toFrontMatter($block[$blockField->handle] ?? '', $blockField);

                if (! in_array($value, [null, '', []], true)) {
                    $map[$type->key($blockField)] = $value;
                }
            }

            $stored[] = $map;
        }

        return $stored;
    }

    /**
     * Only the keys the block's blueprint lists come into the form, so saving in the admin drops any other key
     * written into a block by hand.
     */
    public function fromFrontMatter(mixed $stored, Field $field): string
    {
        $items = is_array($stored) && array_is_list($stored) ? array_values(array_filter($stored, static fn (mixed $item): bool => is_array($item) && is_string($item['type'] ?? null))) : [];

        $blueprints = self::blueprintsFor(array_column($items, 'type'));
        $blocks     = [];

        foreach ($items as $item) {
            $block = ['type' => $item['type']];

            foreach ($blueprints[$item['type']]->fields ?? [] as $blockField) {
                $type                       = FieldTypes::get($blockField->type);
                $block[$blockField->handle] = $type->fromFrontMatter($item[$type->key($blockField)] ?? null, $blockField);
            }

            $blocks[] = $block;
        }

        return ChoiceList::encode($blocks);
    }

    /**
     * The block types the field allows: the ones `blocks` lists, or else every file in resources/blueprints/blocks.
     *
     * @return list<string>
     */
    public static function types(Field $field): array
    {
        $named = $field->options['blocks'] ?? [];
        $named = array_values(array_unique(array_filter(is_array($named) ? $named : [$named], static fn (mixed $type): bool => is_string($type) && $type !== '')));

        if ($named !== []) {
            return $named;
        }

        $all = array_map(static fn (string $file): string => basename($file, '.yaml'), glob(rtrim(config('Content')->blueprintPath, '/') . '/blocks/*.yaml') ?: []);
        $all = array_filter($all, static fn (string $type): bool => preg_match(Blueprint::NAME, $type) === 1);
        sort($all);

        return $all;
    }

    public static function bound(Field $field, string $key): ?int
    {
        $bound = $field->options[$key] ?? null;

        return is_int($bound) && $bound > 0 ? $bound : null;
    }

    /**
     * The blocks of a form value, each with a `type` and the text of its fields.
     *
     * @return list<array<string, string>>
     */
    public static function decode(string $input): array
    {
        $items = json_decode($input, true);
        $items = is_array($items) && array_is_list($items) ? $items : [];

        return array_values(array_map(
            static fn (array $item): array => array_map(static fn (mixed $value): string => is_string($value) ? $value : '', $item) + ['type' => ''],
            array_filter($items, is_array(...)),
        ));
    }

    /**
     * The title of the type of the block at a position, or the type itself when it has no blueprint.
     */
    private static function titleOf(mixed $value, int $position): string
    {
        $type = self::decode(is_string($value) ? $value : '')[$position - 1]['type'] ?? '';

        return (self::blueprintsFor([$type])[$type] ?? null)?->title ?? $type;
    }

    /**
     * @param list<string> $types
     *
     * @return array<string, Blueprint> by type, without those whose blueprint can't be loaded
     */
    private static function blueprintsFor(array $types): array
    {
        $blueprints = [];

        foreach (array_unique($types) as $type) {
            try {
                $blueprints[$type] = Blueprint::loadBlock(config('Content')->blueprintPath, $type);
            } catch (ContentException) {
                // The type is refused when the form is validated.
            }
        }

        return $blueprints;
    }
}
