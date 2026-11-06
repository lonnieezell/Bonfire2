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
        $value = $this->data[$name] ?? null;

        $field = $this->blueprint?->field($name);

        if ($value !== null && $field !== null) {
            $value = $field->cast($value);
        }

        return $value === null || $value === '' ? $default : $value;
    }

    /**
     * The block through its partial, which is `view('blocks/<type>')` unless `Config\Content::$blockViewPath` says
     * otherwise, with the block as `$block`. View Components work inside the partial.
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
