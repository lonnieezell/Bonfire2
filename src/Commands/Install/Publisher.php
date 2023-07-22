<?php

namespace Bonfire\Commands\Install;

use CodeIgniter\Autoloader\FileLocator;
use CodeIgniter\CLI\CLI;

/**
 * ContentReplacer
 *
 * Aids in replacing text within a string.
 * Used to update the config files when
 * they've been copied.
 */
class Publisher
{
    private string $destination;
    private FileLocator $locator;

    public function __construct()
    {
        $this->locator = service('locator');
    }

    /**
     * Set the destination path.
     */
    public function setDestination(string $destination): self
    {
        if (! is_dir($destination)) {
            CLI::error('The destination directory does not exist.');

            exit(1);
        }

        $this->destination = $destination;

        return $this;
    }

    public function publishClass(string $className): void
    {
        $file = $this->locator->locateFile($className);

        if (! $file) {
            CLI::error("The class '{$className}' could not be found.");

            return;
        }

        // Don't overwrite existing files
        if (file_exists($this->destination . $file)) {
            CLI::error("The file '{$file}' already exists. Skipping.");

            return;
        }

        $namespace    = explode('\\', $className);
        $rawClassName = array_pop($namespace);
        $namespace    = implode('\\', $namespace);

        $content = file_get_contents($file);

        $replace = [
            $namespace                           => 'Config',
            'BaseConfig'                         => 'Bonfire' . $rawClassName,
            'use CodeIgniter\Config\BaseConfig;' => "use {$className} as Bonfire{$rawClassName};",
        ];

        if ($className === 'Bonfire\Assets\Config\Assets') {
            $replace['__DIR__ . \'/../../../themes'] = 'ROOTPATH . \'/themes';
        }

        $content = $this->replace($content, $replace);

        $destination = $this->destination . $rawClassName . '.php';

        $this->writeFile($destination, $content);
    }

    /**
     * @param array $replaces [search => replace]
     */
    public function replace(string $content, array $replaces): string
    {
        return strtr($content, $replaces);
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

    public function copyDirectory($source, $destination)
    {
        if (is_dir($source)) {
            @mkdir($destination);
            $directory = dir($source);

            while (false !== ($readdirectory = $directory->read())) {
                if ($readdirectory === '.' || $readdirectory === '..') {
                    continue;
                }
                $PathDir = $source . '/' . $readdirectory;
                if (is_dir($PathDir)) {
                    $this->copyDirectory($PathDir, $destination . '/' . $readdirectory);

                    continue;
                }
                copy($PathDir, $destination . '/' . $readdirectory);
            }

            $directory->close();
        } else {
            copy($source, $destination);
        }
    }
}
