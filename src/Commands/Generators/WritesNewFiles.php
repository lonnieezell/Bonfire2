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

use CodeIgniter\CLI\CLI;

/**
 * Writes a generator's files into the app without ever overwriting one, saying which it created and which it skipped.
 */
trait WritesNewFiles
{
    /**
     * @param array<string, string> $files the contents of each file, by path
     *
     * @return bool false when a file couldn't be written, which stops the rest
     */
    private function writeNewFiles(array $files): bool
    {
        foreach ($files as $file => $contents) {
            if (is_file($file)) {
                CLI::write('Skipped ' . clean_path($file) . ', which exists.', 'yellow');

                continue;
            }

            if (! is_dir(dirname($file))) {
                @mkdir(dirname($file), 0775, true);
            }

            if (@file_put_contents($file, $contents) === false) {
                CLI::error("Couldn't write " . clean_path($file) . '.');

                return false;
            }

            CLI::write('Created ' . clean_path($file), 'green');
        }

        return true;
    }
}
