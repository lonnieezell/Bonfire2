<?php

namespace Bonfire\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BreakingChangesNotification extends BaseCommand
{
    protected $group       = 'Bonfire';
    protected $name        = 'notify:breaking-changes';
    protected $description = 'Notifies about breaking changes after composer update.';

    public function run(array $params)
    {
        $lastUpdateFile = BFPATH . '../last-update.txt';
        $changelogFile  = BFPATH . '../docs/intro/changelog.md';

        // Read the last update date
        $lastUpdateDate = $this->getLastUpdateDate($lastUpdateFile);

        // Read the changelog file
        $breakingChangeDate = $this->getLatestBreakingChangeDate($changelogFile);

        if ($breakingChangeDate && (! $lastUpdateDate || $lastUpdateDate < $breakingChangeDate)) {
            CLI::write(
                '======= WARNING: =======' . PHP_EOL .
                'Breaking changes since the previous update of your Bonfire install detected. ' . PHP_EOL .
                'You may need to update your app manually. ' . PHP_EOL .
                'Please read the docs/intro/changelog.md file for more information.',
                'yellow',
            );
            CLI::write('Latest breaking change date: ' . $breakingChangeDate, 'yellow');

            // Write new last update date
            file_put_contents($lastUpdateFile, date('Y-m-d'));
        } else {
            CLI::write('No new breaking changes in Bonfire detected since previous update.', 'green');
        }
    }

    private function getLastUpdateDate(string $filePath): ?string
    {
        if (file_exists($filePath)) {
            return trim(file_get_contents($filePath));
        }

        // Create the file if it does not exist
        file_put_contents($filePath, '');

        return null;
    }

    private function getLatestBreakingChangeDate(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $file = fopen($filePath, 'rb');
        if ($file) {
            while (($line = fgets($file)) !== false) {
                if (str_contains($line, '(breaking change)')) {
                    preg_match('/## (\d{1,2} \w+ \d{4})/', $line, $matches);
                    if (isset($matches[1])) {
                        fclose($file);

                        return date('Y-m-d', strtotime($matches[1]));
                    }
                }
            }
            fclose($file);
        }

        return null;
    }
}
