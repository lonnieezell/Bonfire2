<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The file a queued run wrote for the admin to download, as JSON of its path and name.
 */
class AddFileToActionRuns extends Migration
{
    public function up()
    {
        $this->forge->addColumn('action_runs', [
            'file' => [
                'type' => 'text',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('action_runs', 'file');
    }
}
