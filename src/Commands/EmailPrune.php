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

use Bonfire\Email\Outbox;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class EmailPrune extends BaseCommand
{
    protected $group       = 'Bonfire';
    protected $name        = 'email:prune';
    protected $description = 'Deletes Outbox rows older than Email.logRetentionDays.';
    protected $usage       = 'email:prune';

    public function run(array $params)
    {
        CLI::write((new Outbox())->prune() . ' Outbox row(s) deleted.');
    }
}
