<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * The old URIs of entries whose URI has changed, kept in `redirects.yaml` at the content root, not in the index, because
 * a rebuild of the index can't bring them back. Each old URI names the id of the entry that has moved, not
 * its new URI, so a second move needs no change to the first, and the file can't make a chain or a loop.
 *
 * Whether the entry can be shown is for the lookup to decide, so an entry that is restored or published brings its redirects back.
 */
final readonly class Redirects
{
    public const FILE = 'redirects.yaml';

    private string $root;

    public function __construct(string $contentRoot)
    {
        $this->root = rtrim($contentRoot, '/') . '/';
    }

    /**
     * @return array<string, string> the id of the entry each old URI now belongs to
     *
     * @throws ContentException when the file can't be read
     */
    public function all(): array
    {
        $file = $this->root . self::FILE;
        $raw  = is_file($file) ? file_get_contents($file) : '';

        if ($raw === false) {
            throw new ContentException(self::FILE . ' could not be read.');
        }

        try {
            $map = Yaml::parse($raw);
        } catch (ParseException $e) {
            throw new ContentException(self::FILE . " is not valid YAML: {$e->getMessage()}", 0, $e);
        }

        if ($map !== null && ! is_array($map)) {
            throw new ContentException(self::FILE . ' must be a YAML mapping of old URIs to entry ids.');
        }

        $redirects = [];

        foreach ($map ?? [] as $uri => $id) {
            if (is_string($uri) && ContentFiles::isUuid($id)) {
                $redirects[Collection::normalizeUri($uri)] = strtolower($id);
            }
        }

        return $redirects;
    }

    /**
     * Remembers the old URI of each entry that has moved, and forgets the redirects from the URIs that entries
     * hold now, so a live entry always wins, and an entry that moves back to a URI leaves no loop. The file is
     * written only if that changes it.
     *
     * @param array<string, array{string|null, string|null}> $moves the old and new URI of each entry, by id
     *
     * @throws ContentException when the file can't be read or written. It is never overwritten if it can't be read.
     */
    public function record(array $moves): void
    {
        if ($moves === []) {
            return;
        }

        $files = new ContentFiles($this->root);

        $files->locked('redirects', function () use ($moves, $files): void {
            $before = $redirects = $this->all();

            foreach ($moves as $id => [$old, $new]) {
                if ($old !== null && $new !== null && $old !== $new) {
                    $redirects[$old] = $id;
                }
            }

            foreach ($moves as [, $new]) {
                unset($redirects[(string) $new]);
            }

            ksort($before);
            ksort($redirects);

            if ($redirects === $before) {
                return;
            }

            $files->write(self::FILE, Yaml::dump($redirects));
        });
    }
}
