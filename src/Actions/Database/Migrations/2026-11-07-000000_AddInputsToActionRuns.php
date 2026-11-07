<?php

namespace Bonfire\Actions\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * What the admin typed into the action's fields, as JSON, so a queued run gets the same input as one in the request.
 */
class AddInputsToActionRuns extends Migration
{
    public function up()
    {
        $this->forge->addColumn('action_runs', [
            'inputs' => [
                'type' => 'text',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('action_runs', 'inputs');
    }
}
