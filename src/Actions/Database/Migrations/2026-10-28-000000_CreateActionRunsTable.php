<?php

namespace Bonfire\Actions\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Queued action runs and their progress.
 */
class CreateActionRunsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'int',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'list' => [
                'type'       => 'varchar',
                'constraint' => 100,
            ],
            'action' => [
                'type'       => 'varchar',
                'constraint' => 100,
            ],
            'user_id' => [
                'type'       => 'int',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ids' => [
                // MySQL's TEXT holds about 10,000 IDs; large runs are the ones that get queued.
                'type' => $this->db->DBDriver === 'MySQLi' ? 'mediumtext' : 'text',
            ],
            'total' => [
                'type'     => 'int',
                'unsigned' => true,
                'default'  => 0,
            ],
            'processed' => [
                'type'     => 'int',
                'unsigned' => true,
                'default'  => 0,
            ],
            'skipped' => [
                'type'     => 'int',
                'unsigned' => true,
                'default'  => 0,
            ],
            'status' => [
                'type'       => 'varchar',
                'constraint' => 20,
            ],
            'message' => [
                'type' => 'text',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->createTable('action_runs', true);
    }

    public function down()
    {
        $this->forge->dropTable('action_runs', true);
    }
}
