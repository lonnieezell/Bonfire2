<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Fields;

/**
 * Data a form sends back to the server, signed with the app's encryption key so a request can't change it. The purpose
 * is part of the signature, so a token made for one endpoint is refused by another.
 */
final class SignedToken
{
    /**
     * The token, or the empty string when the app has no key to sign with.
     *
     * @param array<string, mixed> $data
     */
    public static function encode(string $purpose, array $data): string
    {
        $key = self::key();

        if ($key === '') {
            return '';
        }

        $payload = rtrim(strtr(base64_encode(json_encode($data, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        return $payload . '.' . self::sign($purpose, $payload, $key);
    }

    /**
     * What encode() was given, or null for anything else: no token, one that was changed, or one signed with another key
     * or for another purpose.
     *
     * @return array<string, mixed>|null
     */
    public static function decode(string $purpose, mixed $token): ?array
    {
        $key = self::key();

        if ($key === '' || ! is_string($token)) {
            return null;
        }

        $parts = explode('.', $token);

        if (count($parts) !== 2 || ! hash_equals(self::sign($purpose, $parts[0], $key), $parts[1])) {
            return null;
        }

        $data = json_decode((string) base64_decode(strtr($parts[0], '-_', '+/'), true), true);

        return is_array($data) ? $data : null;
    }

    private static function sign(string $purpose, string $payload, string $key): string
    {
        return hash_hmac('sha256', $purpose . '|' . $payload, $key);
    }

    private static function key(): string
    {
        return (string) config('Encryption')->key;
    }
}
