<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Core;

use Bonfire\Config\Bonfire;

/**
 * Reads the breaking changes out of the changelog that ships with the installed
 * version, and remembers the one the user has already been told about.
 *
 * The marker has to outlive `composer update`, which replaces everything in the
 * package folder, so it is kept in writable/ by default. See
 * Config\Bonfire::$breakingChangeStateFile.
 */
final readonly class BreakingChanges
{
    /**
     * The changelog shipped with the package, relative to its root.
     */
    public const CHANGELOG_PATH = 'docs/intro/changelog.md';

    /**
     * The marker older versions kept in the package folder, where composer
     * deleted it on every update.
     */
    public const LEGACY_STATE_PATH = 'last-update.txt';

    /**
     * The changelog of the installed version.
     */
    private string $changelogPath;

    /**
     * Where the newest reported breaking change is remembered.
     */
    private string $statePath;

    public function __construct(?string $changelogPath = null, ?string $statePath = null)
    {
        $this->changelogPath = $changelogPath ?? self::defaultChangelogPath();
        $this->statePath     = $statePath ?? self::defaultStatePath();
    }

    /**
     * The changelog belonging to the package this code was installed with.
     */
    public static function defaultChangelogPath(): string
    {
        return BFPATH . '../' . self::CHANGELOG_PATH;
    }

    /**
     * A path outside the package folder, so that `composer update` cannot
     * delete the marker. Writable, but not cache: clearing the cache should
     * not bring back warnings for changes the user has already read.
     *
     * The published copy of the config is `Config\Bonfire`, extending this
     * package's class, so that is the one an install can change.
     */
    public static function defaultStatePath(): string
    {
        $configClass = class_exists(\Config\Bonfire::class) ? \Config\Bonfire::class : Bonfire::class;
        $configured  = config($configClass)->breakingChangeStateFile ?? null;

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return WRITEPATH . 'bonfire-breaking-changes.json';
    }

    public function changelogPath(): string
    {
        return $this->changelogPath;
    }

    public function statePath(): string
    {
        return $this->statePath;
    }

    /**
     * Whether the changelog can be read at all. When it cannot, nothing can be
     * said about breaking changes, and the user should not be warned.
     */
    public function hasChangelog(): bool
    {
        return is_file($this->changelogPath) && is_readable($this->changelogPath);
    }

    /**
     * The newest breaking change in the changelog.
     *
     * The hash covers every breaking section, because a note is often added to
     * an existing heading: the date then stays where it was, while the text the
     * user has to read grows.
     *
     * @return array{date: string, hash: string}|null null when the changelog is
     *                                                missing or lists no breaking change
     */
    public function latest(): ?array
    {
        if (! $this->hasChangelog()) {
            return null;
        }

        $contents = file_get_contents($this->changelogPath);

        if ($contents === false) {
            return null;
        }

        $newest   = null;
        $sections = [];
        $current  = null;

        foreach (preg_split('/\R/', $contents) as $line) {
            if (preg_match('/^##\s+(\d{1,2}\s+\w+\s+\d{4})(.*)$/', $line, $matches) === 1) {
                $timestamp = str_contains(strtolower($matches[2]), '(breaking change)')
                    ? strtotime($matches[1])
                    : false;

                $current = $timestamp === false ? null : $line;

                if ($timestamp !== false) {
                    $date = date('Y-m-d', $timestamp);

                    $newest     = $newest === null || $date > $newest ? $date : $newest;
                    $sections[] = $line;
                }

                continue;
            }

            if ($current !== null) {
                $sections[] = $line;
            }
        }

        if ($newest === null) {
            return null;
        }

        return [
            'date' => $newest,
            'hash' => hash('sha256', implode("\n", array_map(rtrim(...), $sections))),
        ];
    }

    /**
     * The breaking change the user has already been told about.
     *
     * @return array{date: string, hash: string}|null
     */
    public function acknowledged(): ?array
    {
        if (! is_file($this->statePath)) {
            return null;
        }

        $contents = file_get_contents($this->statePath);

        if ($contents === false) {
            return null;
        }

        $state = json_decode($contents, true);

        if (! is_array($state) || ! is_string($state['date'] ?? null) || $state['date'] === '') {
            return null;
        }

        return [
            'date' => $state['date'],
            'hash' => is_string($state['hash'] ?? null) ? $state['hash'] : '',
        ];
    }

    /**
     * Whether the user still has to be told about $latest.
     *
     * A marker written by an older version holds a date only, so its hash is
     * empty and the dates decide on their own.
     *
     * @param array{date: string, hash: string}      $latest
     * @param array{date: string, hash: string}|null $acknowledged
     */
    public function isNew(array $latest, ?array $acknowledged): bool
    {
        if ($acknowledged === null) {
            return true;
        }

        if ($latest['date'] !== $acknowledged['date']) {
            return $latest['date'] > $acknowledged['date'];
        }

        return $acknowledged['hash'] !== '' && $latest['hash'] !== $acknowledged['hash'];
    }

    /**
     * Remembers that the user has been told about $latest.
     *
     * @param array{date: string, hash: string} $latest
     *
     * @return bool false when the marker cannot be written
     */
    public function acknowledge(array $latest): bool
    {
        $directory = dirname($this->statePath);

        if (is_file($directory)) {
            return false;
        }

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            return false;
        }

        if (! is_writable($directory)) {
            return false;
        }

        $json = json_encode($latest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return false;
        }

        return file_put_contents($this->statePath, $json . "\n") !== false;
    }

    /**
     * Carries over the marker an older version left inside the package folder,
     * and removes that file.
     *
     * @return bool whether a marker was carried over
     */
    public function migrateLegacy(string $legacyPath): bool
    {
        if (is_file($this->statePath) || ! is_file($legacyPath)) {
            return false;
        }

        $contents = file_get_contents($legacyPath);
        $date     = $contents === false ? '' : trim($contents);

        $migrated = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && $this->acknowledge(['date' => $date, 'hash' => '']);

        unlink($legacyPath);

        return $migrated;
    }
}
