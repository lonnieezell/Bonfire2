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
use Bonfire\Privacy\PrunesPersonalData;
use Bonfire\Users\User;
use CodeIgniter\I18n\Time;

/**
 * The consent the user gave while signed in.
 */
class ConsentPersonalData implements PersonalDataProvider, PrunesPersonalData
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
        model(ConsentRecordModel::class)->where('user_id', $user->id)->delete();
    }

    /**
     * Deletes records older than the retention, except each browser's
     * latest one while its consent can still be in use.
     */
    public function prune(): int
    {
        $days = config('Privacy')->retention['consents'] ?? null;

        if ($days === null) {
            return 0;
        }

        $db      = db_connect();
        $records = model(ConsentRecordModel::class);
        $latest  = $db->table('consent_records')->selectMax('id')->groupBy('consent_id');

        $records->where('created_at <', Time::now()->subDays($days)->toDateTimeString())
            ->groupStart()
            // Wrapped in a derived table, as MySQL can't select from the table it deletes from
            ->whereNotIn('id', $db->newQuery()->fromSubquery($latest, 'latest')->select('id'))
            ->orWhere('created_at <', Time::now()->subDays((int) setting('Consent.consentLength'))->toDateTimeString())
            ->groupEnd()
            ->delete();

        return $db->affectedRows();
    }
}
