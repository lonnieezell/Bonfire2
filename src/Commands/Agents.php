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

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Composer\InstalledVersions;

class Agents extends BaseCommand
{
    public const START_MARKER = '<!-- bonfire:start -->';
    public const END_MARKER   = '<!-- bonfire:end -->';

    protected $group       = 'Bonfire';
    protected $name        = 'bf:agents';
    protected $description = 'Writes Bonfire guidance for AI coding agents into AGENTS.md.';
    protected $usage       = 'bf:agents';

    public function run(array $params)
    {
        $root     = $this->rootPath();
        $path     = $root . 'AGENTS.md';
        $existing = is_file($path) ? @file_get_contents($path) : '';

        if ($existing === false) {
            CLI::error("Could not read {$path}.");

            return 1;
        }

        $problem = $this->markerProblem($existing);

        // Repairing broken markers could delete the user's own text, so
        // they are left for the user to fix.
        if ($problem !== null) {
            CLI::error("Nothing was written: {$path} has {$problem}. Fix or remove the Bonfire markers and run this again.");

            return 1;
        }

        $section = $this->section();
        $start   = strpos($existing, self::START_MARKER);

        if ($start !== false) {
            $end      = strpos($existing, self::END_MARKER) + strlen(self::END_MARKER);
            $contents = substr_replace($existing, $section, $start, $end - $start);
        } elseif ($existing !== '') {
            $contents = $existing . (str_ends_with($existing, "\n") ? "\n" : "\n\n") . $section . "\n";
        } else {
            $contents = $section . "\n";
        }

        if (@file_put_contents($path, $contents) === false) {
            CLI::error("Could not write {$path}.");

            return 1;
        }

        CLI::write("Wrote the Bonfire section to {$path}.", 'green');

        $claudePath = $root . 'CLAUDE.md';

        if (is_file($claudePath) && ! str_contains(file_get_contents($claudePath), '@AGENTS.md')) {
            CLI::write('Claude Code reads CLAUDE.md: add a line with @AGENTS.md to it so it sees this guidance too.', 'yellow');
        }

        CLI::write('Tip: add "php spark bf:agents" to the post-update-cmd scripts in composer.json, next to "php spark notify:breaking-changes", to keep the section in step with the installed version.');

        return 0;
    }

    /**
     * What is wrong with the Bonfire markers in the file, or null when there
     * are none or exactly one pair in order.
     */
    private function markerProblem(string $contents): ?string
    {
        $starts = substr_count($contents, self::START_MARKER);
        $ends   = substr_count($contents, self::END_MARKER);

        if ($starts === 0 && $ends === 0) {
            return null;
        }

        if ($starts > 1 || $ends > 1) {
            return 'more than one ' . self::START_MARKER . ' or ' . self::END_MARKER;
        }

        if ($ends === 0) {
            return self::START_MARKER . ' without ' . self::END_MARKER;
        }

        if ($starts === 0) {
            return self::END_MARKER . ' without ' . self::START_MARKER;
        }

        if (strpos($contents, self::END_MARKER) < strpos($contents, self::START_MARKER)) {
            return self::END_MARKER . ' before ' . self::START_MARKER;
        }

        return null;
    }

    /**
     * The project root, where AGENTS.md lives.
     */
    protected function rootPath(): string
    {
        return ROOTPATH;
    }

    /**
     * The Bonfire section, markers included, for the installed version.
     */
    private function section(): string
    {
        $template = file_get_contents(__DIR__ . '/Agents/bonfire.md');
        $version  = InstalledVersions::getPrettyVersion('lonnieezell/bonfire');

        return self::START_MARKER . "\n"
            . str_replace('{version}', $version, trim($template)) . "\n"
            . self::END_MARKER;
    }
}
