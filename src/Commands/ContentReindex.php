<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Commands;

use Bonfire\Content\Indexer;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ContentReindex extends BaseCommand
{
    protected $group       = 'Bonfire';
    protected $name        = 'content:reindex';
    protected $description = 'Brings the content index in line with the content files.';
    protected $usage       = 'content:reindex [--fresh]';

    /**
     * @var array<string, string>
     */
    protected $options = [
        '--fresh' => 'Empty the index and rebuild it.',
    ];

    public function run(array $params)
    {
        $result = (new Indexer(config('Content')->contentPath))->reindex(array_key_exists('fresh', $params));

        foreach ($result->errors as $path => $error) {
            CLI::error("{$path}: {$error}");
        }

        if ($result->failure !== null) {
            CLI::error($result->failure);

            return EXIT_ERROR;
        }

        CLI::write("{$result->added} added, {$result->updated} updated, {$result->removed} removed, {$result->unchanged} unchanged.");
    }
}
