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

use Bonfire\Core\BreakingChanges;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BreakingChangesNotification extends BaseCommand
{
    protected $group       = 'Bonfire';
    protected $name        = 'notify:breaking-changes';
    protected $description = 'Notifies about breaking changes after composer update.';
    protected $usage       = 'notify:breaking-changes';

    public function run(array $params)
    {
        $breakingChanges = new BreakingChanges();

        // The marker older versions left in the package folder is deleted by
        // every composer update, so it is carried over before it goes.
        $breakingChanges->migrateLegacy(BFPATH . '../' . BreakingChanges::LEGACY_STATE_PATH);

        $latest = $breakingChanges->latest();

        if ($latest === null) {
            if ($breakingChanges->hasChangelog()) {
                CLI::write('No breaking changes in the Bonfire changelog yet.', 'green');
            } else {
                CLI::write(
                    'Could not read the Bonfire changelog, so breaking changes were not checked: '
                    . $breakingChanges->changelogPath(),
                    'yellow',
                );
            }

            return 0;
        }

        if (! $breakingChanges->isNew($latest, $breakingChanges->acknowledged())) {
            CLI::write('No new breaking changes in Bonfire detected since previous update.', 'green');

            return 0;
        }

        CLI::write(
            '======= WARNING: =======' . PHP_EOL
            . 'Breaking changes since the previous update of your Bonfire install detected. ' . PHP_EOL
            . 'You may need to update your app manually. ' . PHP_EOL
            . 'Please read the changelog for more information:' . PHP_EOL
            . $breakingChanges->changelogPath(),
            'yellow',
        );
        CLI::write('Latest breaking change date: ' . $latest['date'], 'yellow');

        if (! $breakingChanges->acknowledge($latest)) {
            CLI::write(
                'Note: this warning could not be saved to ' . $breakingChanges->statePath() . PHP_EOL
                . 'so it will be shown again after the next update.',
                'yellow',
            );
        }

        return 0;
    }
}
