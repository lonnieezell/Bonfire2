<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Consent;

use Bonfire\Consent\Models\ConsentRecordModel;
use Bonfire\Privacy\PersonalDataProvider;
use Bonfire\Users\User;

/**
 * The consent the user gave while signed in.
 */
class ConsentPersonalData implements PersonalDataProvider
{
    public function label(): string
    {
        return lang('Consent.consentModTitle');
    }

    public function export(User $user): array
    {
        $records = model(ConsentRecordModel::class)->where('user_id', $user->id)->orderBy('id')->findAll();

        return array_map(static fn (array $record) => [
            'consent_id' => $record['consent_id'],
            'consents'   => json_decode($record['consents'], true),
            'created_at' => $record['created_at'],
        ], $records);
    }

    public function erase(User $user): void
    {
    }
}
