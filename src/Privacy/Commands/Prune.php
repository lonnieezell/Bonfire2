<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Privacy\Commands;

use Bonfire\Bonfire;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Prune extends BaseCommand
{
    protected $group       = 'Bonfire';
    protected $name        = 'bf:privacy:prune';
    protected $description = 'Deletes personal data that is past its retention period.';
    protected $usage       = 'bf:privacy:prune';

    public function run(array $params)
    {
        // The CLI serves no admin page, so modules haven't registered their providers yet.
        if (service('personalData')->providers() === []) {
            (new Bonfire())->boot(admin: true);
        }

        foreach (service('personalData')->prune() as $alias => $count) {
            CLI::write("{$alias}: {$count} record(s) deleted.");
        }
    }
}
