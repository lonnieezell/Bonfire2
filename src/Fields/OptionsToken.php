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
    private const PURPOSE = 'fields-options';

    /**
     * @param class-string<Model> $model
     * @param list<string>        $search     the columns the query is matched against
     * @param string|null         $permission what the user needs on top of `admin.access`
     *
     * @throws FieldException when the app has no encryption key to sign with
     */
    public static function create(string $model, string $title, array $search, ?string $permission): string
    {
        $token = SignedToken::encode(self::PURPOSE, ['model' => $model, 'title' => $title, 'search' => $search, 'permission' => $permission]);

        if ($token === '') {
            throw new FieldException(lang('Fields.optionsNoKey'));
        }

        return $token;
    }

    /**
     * What create() was given, or null for anything else: no token, one that was changed, or one signed with another key.
     *
     * @return array{model: class-string<Model>, title: string, search: list<string>, permission: string|null}|null
     */
    public static function read(mixed $token): ?array
    {
        $data = SignedToken::decode(self::PURPOSE, $token);

        if (
            $data === null
            || ! is_string($data['model'] ?? null) || ! is_a($data['model'], Model::class, true)
            || ! is_string($data['title'] ?? null)
            || ! is_array($data['search'] ?? null) || ! array_is_list($data['search']) || array_filter($data['search'], is_string(...)) !== $data['search']
            || ! (($data['permission'] ?? null) === null || is_string($data['permission']))
        ) {
            return null;
        }

        return ['model' => $data['model'], 'title' => $data['title'], 'search' => $data['search'], 'permission' => $data['permission'] ?? null];
    }
}
