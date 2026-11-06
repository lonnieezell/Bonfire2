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

use CodeIgniter\Model;

/**
 * What a typeahead sends to the options endpoint to say which records to search: the model, the column that titles a
 * record, the columns to search and the permission the user needs. It's signed with the app's encryption key, so the
 * endpoint reads nothing the form's own field didn't name.
 */
final class OptionsToken
{
    /**
     * @param class-string<Model> $model
     * @param list<string>        $search     the columns the query is matched against
     * @param string|null         $permission what the user needs on top of `admin.access`
     *
     * @throws FieldException when the app has no encryption key to sign with
     */
    public static function create(string $model, string $title, array $search, ?string $permission): string
    {
        $key = self::key();

        if ($key === '') {
            throw new FieldException(lang('Fields.optionsNoKey'));
        }

        $payload = rtrim(strtr(base64_encode(json_encode(
            ['model' => $model, 'title' => $title, 'search' => $search, 'permission' => $permission],
            JSON_THROW_ON_ERROR,
        )), '+/', '-_'), '=');

        return $payload . '.' . self::sign($payload, $key);
    }

    /**
     * What create() was given, or null for anything else: no token, one that was changed, or one signed with another key.
     *
     * @return array{model: class-string<Model>, title: string, search: list<string>, permission: string|null}|null
     */
    public static function read(mixed $token): ?array
    {
        $key = self::key();

        if ($key === '' || ! is_string($token)) {
            return null;
        }

        $parts = explode('.', $token);

        if (count($parts) !== 2 || ! hash_equals(self::sign($parts[0], $key), $parts[1])) {
            return null;
        }

        $data = json_decode((string) base64_decode(strtr($parts[0], '-_', '+/'), true), true);

        if (
            ! is_array($data)
            || ! is_string($data['model'] ?? null) || ! is_a($data['model'], Model::class, true)
            || ! is_string($data['title'] ?? null)
            || ! is_array($data['search'] ?? null) || ! array_is_list($data['search']) || array_filter($data['search'], is_string(...)) !== $data['search']
            || ! (($data['permission'] ?? null) === null || is_string($data['permission']))
        ) {
            return null;
        }

        return ['model' => $data['model'], 'title' => $data['title'], 'search' => $data['search'], 'permission' => $data['permission'] ?? null];
    }

    private static function sign(string $payload, string $key): string
    {
        return hash_hmac('sha256', 'fields-options|' . $payload, $key);
    }

    private static function key(): string
    {
        return (string) config('Encryption')->key;
    }
}
