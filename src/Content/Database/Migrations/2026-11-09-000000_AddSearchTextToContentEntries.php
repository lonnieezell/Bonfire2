<?php

namespace Bonfire\Content\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The plain text of each entry that searches match. `spark content:reindex` fills it for entries that are already indexed.
 */
class AddSearchTextToContentEntries extends Migration
{
    public function up()
    {
        $this->forge->addColumn('content_entries', [
            'search_text' => [
                // TEXT stops at 64KB on MySQL, and a long post can pass that.
                'type' => $this->db->DBDriver === 'MySQLi' ? 'mediumtext' : 'text',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('content_entries', 'search_text');
    }
}
