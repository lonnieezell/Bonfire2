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
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Fields\FieldTypes;
use Config\Paths;

/**
 * One block of an entry's `blocks` field: its type and the values the file holds for it.
 */
final readonly class Block
{
    /**
     * @param array<array-key, mixed> $data      the block's values, without its `type`
     * @param ?Blueprint              $blueprint what describes the values, or null when the block type's file is gone
     * @param Entry                   $entry     the entry the block belongs to
     */
    public function __construct(
        public string $type,
        private array $data,
        private ?Blueprint $blueprint,
        public Entry $entry,
    ) {
    }

    /**
     * A value, cast by the type of the block blueprint's field that holds it, the way `globals()` casts: a toggle is a
     * bool and an integer an int. A value the file leaves out or empty, or that doesn't fit an integer or date, gives
     * the default.
     */
    public function field(string $name, mixed $default = null): mixed
    {
        $field = $this->blueprint?->field($name);
        $key   = $field === null ? $name : EntryStorage::key(FieldTypes::get($field->type), $field);
        $value = $this->data[$key] ?? null;

        if ($value !== null && $field !== null) {
            $value = EntryStorage::cast($value, $field);
        }

        return $value === null || $value === '' ? $default : $value;
    }

    /**
     * The published fragment a `fragment` block links to. Null for any other block, for a draft, scheduled, trashed or
     * missing fragment, and for an entry outside a fragment collection, which a file written by hand can link to.
     */
    public function fragment(): ?Entry
    {
        $id          = $this->fragmentId();
        $collections = $id === null ? [] : Collection::fragmentHandles(config('Content')->contentPath);

        if ($collections === []) {
            return null;
        }

        $row = (new ContentEntryModel())->published()->whereIn('collection', $collections)->where('id', $id)->first();

        return $row === null ? null : Entry::fromRow($row);
    }

    /**
     * The id a `fragment` block links to, whatever that entry's state.
     */
    private function fragmentId(): ?string
    {
        $ids = $this->type === Blueprint::FRAGMENT_BLOCK ? $this->field(Blueprint::FRAGMENT_BLOCK) : null;

        return is_array($ids) && is_string($ids[0] ?? null) ? strtolower($ids[0]) : null;
    }

    /**
     * The block through its partial, which is `view('blocks/<type>')` unless `Config\Content::$blockViewPath` says
     * otherwise, with the block as `$block`. View Components work inside the partial. A `fragment` block in a cycle of
     * fragments, or nested more than 5 deep, is logged and gives nothing.
     *
     * @throws ContentException when the partial doesn't exist, except in production, where it is logged and gives nothing
     */
    public function render(): string
    {
        return $this->renderFor(ENVIRONMENT === 'production');
    }

    /**
     * What render() does, for either environment.
     *
     * @throws ContentException when the partial doesn't exist and this isn't production
     */
    public function renderFor(bool $production): string
    {
        $fragment = $this->fragmentId();

        if ($fragment === null) {
            return $this->renderPartial($production);
        }

        return FragmentStack::render($this->entry->id, $fragment, fn (): string => $this->renderPartial($production));
    }

    /**
     * @throws ContentException when the partial doesn't exist and this isn't production
     */
    private function renderPartial(bool $production): string
    {
        $folder = trim(config('Content')->blockViewPath, '/');
        $view   = "{$folder}/{$this->type}";
        $file   = rtrim((new Paths())->viewDirectory, '/') . "/{$view}.php";

        // The type comes from a file anyone with access to content can write, so it never names a path. A partial a
        // module ships is found the way view() finds it.
        if (preg_match(Blueprint::NAME, $this->type) !== 1 || (! is_file($file) && service('locator')->locateFile($view, 'Views') === false)) {
            if ($production) {
                log_message('error', 'Content: block partial {partial} is missing, so a {type} block was not rendered.', ['partial' => "{$view}.php", 'type' => $this->type]);

                return '';
            }

            throw new ContentException("The partial for the `{$this->type}` block is missing. Create {$file}.");
        }

        return view($view, ['block' => $this], ['saveData' => false]);
    }
}
