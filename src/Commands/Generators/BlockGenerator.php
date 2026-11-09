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

use Bonfire\Content\Blueprint;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Paths;
use Symfony\Component\Yaml\Yaml;

/**
 * Writes a new block type into the app: its blueprint and a partial that renders its fields. It never overwrites a
 * file, and the files are the app's to change from then on.
 */
class BlockGenerator extends BaseCommand
{
    use RendersTemplates;
    use WritesNewFiles;

    protected $group       = 'Bonfire';
    protected $name        = 'make:block';
    protected $description = 'Writes the blueprint and partial of a new block type.';
    protected $usage       = 'make:block <type>';

    /**
     * @var array<string, string>
     */
    protected $arguments = [
        'type' => 'The handle of the block type, such as hero.',
    ];

    public function run(array $params)
    {
        $type = (string) ($params[0] ?? '');

        if (preg_match(Blueprint::NAME, $type) !== 1) {
            CLI::error("`{$type}` can't name a block type. Use 1 to 100 letters, numbers, _ and -, such as hero.");

            return EXIT_ERROR;
        }

        $config  = config('Content');
        $folder  = trim($config->blockViewPath, '/');
        $partial = rtrim((new Paths())->viewDirectory, '/') . "/{$folder}/{$type}.php";

        // The built-in type needs no blueprint, and a file of that name would replace it.
        $files = $type === Blueprint::FRAGMENT_BLOCK
            ? [$partial => $this->render('fragment')]
            : [
                rtrim($config->blueprintPath, '/') . "/blocks/{$type}.yaml" => $this->render('blueprint', [
                    // Quoted when YAML would read it as something else, such as `True` or `2026`.
                    'title' => Yaml::dump(ucwords(str_replace(['-', '_'], ' ', $type))),
                ]),
                $partial => $this->render('partial', ['type' => $type]),
            ];

        return $this->writeNewFiles($files) ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $template, array $data = []): string
    {
        return $this->renderTemplate(__DIR__ . "/Views/block/{$template}.tpl.php", $data);
    }
}
