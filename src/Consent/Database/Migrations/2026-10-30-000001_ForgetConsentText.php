<?php

namespace Bonfire\Consent\Database\Migrations;

use Bonfire\Consent\Config\Consent;
use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Forgets the consent text saved from Settings → Consent: the message,
 * the policy title and the consents. They now live in config and lang
 * files only, and a stored row would hide them.
 */
class ForgetConsentText extends Migration
{
    public function up()
    {
        $config = config('Settings')->database;

        Database::connect($config['group'] ?? null)->table($config['table'] ?? 'settings')
            ->whereIn('class', array_unique([Consent::class, config('Consent')::class]))
            ->whereIn('key', ['consents', 'consentMessage', 'policyTitle'])
            ->delete();
    }

    public function down()
    {
        // The forgotten values are not kept
    }
}
