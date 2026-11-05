<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Commands\Generators;

/**
 * Renders a generator's .tpl.php template into the source of a file. Templates write <@php and <@= for the tags
 * the written file keeps.
 */
trait RendersTemplates
{
    /**
     * @param array<string, mixed> $data
     */
    private function renderTemplate(string $file, array $data): string
    {
        $render = static function (string $__file, array $__data): string {
            extract($__data);
            ob_start();

            include $__file;

            return (string) ob_get_clean();
        };

        return str_replace(['<@php', '<@='], ['<?php', '<?='], $render($file, $data));
    }
}
