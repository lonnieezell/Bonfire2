<?php

namespace Bonfire\Users\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Database;

/**
 * Settings → Users saved the remember-me length as the string the
 * select posted. Shield hands it to Time::addSeconds(), which requires
 * an int, so "remember me" logins failed with a TypeError.
 *
 * The placeholder "How long to remember..." was saved as "0"; it now
 * falls back to the configured default length.
 */
class CastRememberLengthToInt extends Migration
{
    public function up()
    {
        $config = config('Settings')->database;
        $db     = Database::connect($config['group'] ?? null);
        $table  = $config['table'] ?? 'settings';

        if (! $db->tableExists($table)) {
            return;
        }

        $rows = $db->table($table)
            ->where('key', 'sessionConfig')
            ->like('class', 'Auth', 'before')
            ->get()
            ->getResult();

        foreach ($rows as $row) {
            if ($row->type !== 'array') {
                continue;
            }

            $value = unserialize($row->value);

            if (! is_array($value) || ! array_key_exists('rememberLength', $value)) {
                continue;
            }

            $value['allowRemembering'] = (bool) ($value['allowRemembering'] ?? false);

            $length                  = (int) $value['rememberLength'];
            $value['rememberLength'] = $length > 0
                ? $length
                : (int) config('Auth')->sessionConfig['rememberLength'];

            $db->table($table)
                ->where('id', $row->id)
                ->update(['value' => serialize($value)]);
        }
    }

    public function down()
    {
        // The cast value is not reverted
    }
}
