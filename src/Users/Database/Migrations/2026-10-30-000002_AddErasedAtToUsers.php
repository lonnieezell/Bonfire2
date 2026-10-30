<?php

namespace Bonfire\Users\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * When a user's personal data was erased. Erased users stay soft-deleted
 * for good, so content pointing at them keeps working.
 */
class AddErasedAtToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'erased_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'erased_at');
    }
}
