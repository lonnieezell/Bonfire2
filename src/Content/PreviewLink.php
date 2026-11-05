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

/**
 * A signed, short-lived URL that shows one entry, draft or not, the way the site would.
 * The signature covers the entry's id and the expiry time, so neither can be changed.
 */
final class PreviewLink
{
    /**
     * How long a link works, in seconds.
     */
    public const LIFETIME = 86400;

    /**
     * @param int $expires when the link stops working, as a Unix timestamp
     *
     * @throws ContentException when the app has no encryption key to sign with
     */
    public static function create(string $id, int $expires): string
    {
        $key = self::key();

        if ($key === '') {
            throw new ContentException(lang('Content.previewNoKey'));
        }

        return url_to('content-draft-preview', $id) . '?' . http_build_query([
            'expires'   => $expires,
            'signature' => self::sign($id, $expires, $key),
        ]);
    }

    /**
     * Whether this is a link that create() made for this entry, and it hasn't expired.
     */
    public static function isValid(string $id, mixed $expires, mixed $signature, int $now): bool
    {
        $key = self::key();

        if ($key === '' || ! is_string($expires) || ! ctype_digit($expires) || ! is_string($signature)) {
            return false;
        }

        return (int) $expires >= $now && hash_equals(self::sign($id, (int) $expires, $key), $signature);
    }

    private static function sign(string $id, int $expires, string $key): string
    {
        return hash_hmac('sha256', 'content-preview|' . strtolower($id) . '|' . $expires, $key);
    }

    private static function key(): string
    {
        $key = (string) config('Encryption')->key;

        // Derived, so a signature made for this can't be one for anything else that signs with the app key.
        return $key === '' ? '' : hash_hmac('sha256', 'content-preview', $key);
    }
}
