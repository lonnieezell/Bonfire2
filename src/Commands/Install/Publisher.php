<?php

namespace Bonfire\Commands\Install;

use CodeIgniter\Autoloader\FileLocator;
use CodeIgniter\CLI\CLI;
use InvalidArgumentException;

/**
 * Publishes Bonfire's config classes and themes into the app.
 *
 * What a config copy should say is decided by ContentEdits; this class
 * only reads and writes the files.
 */
class Publisher
{
    private string $destination;
    private readonly FileLocator $locator;

    public function __construct()
    {
        $this->locator = service('locator');
    }

    /**
     * Set the destination path.
     *
     * @throws InvalidArgumentException when the directory does not exist
     */
    public function setDestination(string $destination): self
    {
        if (! is_dir($destination)) {
            throw new InvalidArgumentException('The destination directory does not exist.');
        }

        $this->destination = $destination;

        return $this;
    }

    /**
     * Copies a Bonfire config class into the destination as the app's own.
     * A copy that could not be made correctly is not written.
     *
     * @return list<string> What went wrong, empty when the file was published or already there.
     */
    public function publishClass(string $className): array
    {
        $file = $this->locator->locateFile($className);

        if (! $file) {
            CLI::error("The class '{$className}' could not be found.");

            return ["The class '{$className}' could not be found."];
        }

        // Don't overwrite existing files
        if (file_exists($this->destination . $file)) {
            CLI::error("The file '{$file}' already exists. Skipping.");

            return [];
        }

        $rawClassName = array_slice(explode('\\', $className), -1)[0];

        $edit = ContentEdits::publishedConfig(file_get_contents($file), $className);

        if (! $edit->complete()) {
            $message = "Config\\{$rawClassName} was not published. Bonfire's {$rawClassName} no longer has the text the installer expects: "
                . implode(', ', $edit->missing);

            CLI::error('  ' . $message);

            return [$message];
        }

        $this->writeFile($this->destination . $rawClassName . '.php', $edit->content);

        return [];
    }

    /**
     * Write a file, catching any exceptions and showing a
     * nicely formatted error.
     *
     * @param string $path Relative file path like 'Config/Auth.php'.
     */
    protected function writeFile(string $path, string $content): void
    {
        $cleanPath = clean_path($path);

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (file_exists($path)) {
            $overwrite = (bool) CLI::getOption('f');

            if (
                ! $overwrite
                && CLI::prompt("  File '{$cleanPath}' already exists in destination. Overwrite?", ['n', 'y']) === 'n'
            ) {
                CLI::error("  Skipped {$cleanPath}.");

                return;
            }
        }

        if (write_file($path, $content)) {
            CLI::write(CLI::color('  Created: ', 'green') . $cleanPath);
        } else {
            CLI::error("  Error creating {$cleanPath}.");
        }
    }

    /**
     * Copies one file, such as a theme file, to `$destination`.
     *
     * A file that is there already is the app's own copy, which may have been
     * edited, so it is left alone unless `$overwrite` is set.
     *
     * @return 'copied'|'failed'|'overwritten'|'skipped'
     */
    public function publishFile(string $source, string $destination, bool $overwrite = false): string
    {
        $exists = file_exists($destination);

        if ($exists && ! $overwrite) {
            return 'skipped';
        }

        $directory = dirname($destination);

        if (! is_dir($directory) && ! @mkdir($directory, 0777, true) && ! is_dir($directory)) {
            return 'failed';
        }

        if (! @copy($source, $destination)) {
            return 'failed';
        }

        return $exists ? 'overwritten' : 'copied';
    }

    /**
     * Copies every file under `$source` into `$destination`, keeping the
     * folder layout. See publishFile() for files that exist already.
     *
     * @return array{copied: int, skipped: int, failed: list<string>} Files written, files left
     *                                                                as they were, and files that could not be written.
     *
     * @throws InvalidArgumentException when `$source` is not a directory
     */
    public function copyDirectory(string $source, string $destination, bool $overwrite = false): array
    {
        if (! is_dir($source)) {
            throw new InvalidArgumentException("The source directory '{$source}' does not exist.");
        }

        $result = ['copied' => 0, 'skipped' => 0, 'failed' => []];

        foreach (scandir($source) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $from = $source . '/' . $entry;
            $to   = $destination . '/' . $entry;

            if (is_dir($from)) {
                $nested = $this->copyDirectory($from, $to, $overwrite);

                $result['copied'] += $nested['copied'];
                $result['skipped'] += $nested['skipped'];
                array_push($result['failed'], ...$nested['failed']);

                continue;
            }

            match ($this->publishFile($from, $to, $overwrite)) {
                'skipped' => $result['skipped']++,
                'failed'  => $result['failed'][] = $to,
                default   => $result['copied']++,
            };
        }

        return $result;
    }
}
