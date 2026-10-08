<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools\Libraries;

/**
 * The log files on disk. Files are only ever addressed by a validated name
 * like `log-2024-01-31` (no path, no extension), so callers never build paths.
 */
class LogStore
{
    private const NAME_PATTERN = '/^log-\d{4}-\d{2}-\d{2}$/';
    private const EXTENSION    = '.log';

    private readonly string $path;
    private readonly Logs $parser;

    public function __construct(string $path = WRITEPATH . 'logs/', ?Logs $parser = null)
    {
        $this->path   = rtrim($path, '/') . '/';
        $this->parser = $parser ?? new Logs();
    }

    /**
     * The names of all log files, newest first.
     *
     * @return list<string>
     */
    public function names(): array
    {
        $names = array_map(
            static fn (string $file): string => basename($file, self::EXTENSION),
            glob($this->path . 'log-*' . self::EXTENSION) ?: [],
        );
        $names = array_filter($names, $this->isValidName(...));

        rsort($names);

        return $names;
    }

    public function has(string $name): bool
    {
        return $this->isValidName($name) && is_file($this->fileFor($name));
    }

    /**
     * The count of entries per level, as html for the logs list.
     */
    public function summary(string $name): string
    {
        return $this->parser->countLogLevels($this->fileFor($name));
    }

    /**
     * The parsed entries of a log file.
     */
    public function entries(string $name): array
    {
        return $this->parser->processFileLogs($this->fileFor($name));
    }

    /**
     * The links and labels for stepping to the previous and next log file.
     */
    public function neighbours(string $name): array
    {
        $names = array_reverse($this->names());
        $index = array_search($name, $names, true);

        $prev = $index > 0 ? $names[$index - 1] : null;
        $next = $index !== false && $index < count($names) - 1 ? $names[$index + 1] : null;

        // 'log-2024-01-31' -> '2024-01-31'
        $label = static fn (?string $name): string => substr($name ?? '', 4, 10);

        return [
            'prev' => ['link' => $prev, 'label' => $label($prev)],
            'curr' => ['label' => $label($name)],
            'next' => ['link' => $next, 'label' => $label($next)],
        ];
    }

    /**
     * Deletes the named log files. Names that are not existing log files are ignored.
     *
     * @param list<string> $names
     *
     * @return int The number of files deleted
     */
    public function delete(array $names): int
    {
        $deleted = 0;

        foreach ($names as $name) {
            if (is_string($name) && $this->has($name) && unlink($this->fileFor($name))) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @return int The number of files deleted
     */
    public function deleteAll(): int
    {
        return $this->delete($this->names());
    }

    private function isValidName(string $name): bool
    {
        return preg_match(self::NAME_PATTERN, $name) === 1;
    }

    private function fileFor(string $name): string
    {
        return $this->path . $name . self::EXTENSION;
    }
}
