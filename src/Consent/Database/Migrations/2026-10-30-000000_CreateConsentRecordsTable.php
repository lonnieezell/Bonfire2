<?php

namespace Bonfire\Consent\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Every consent a visitor saves, so the site can show when a browser
 * or user consented, and to what. No IP address or user agent is kept.
 */
class CreateConsentRecordsTable extends Migration
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
            'consent_id' => [
                'type'       => 'varchar',
                'constraint' => 32,
            ],
            'user_id' => [
                'type'       => 'int',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'consents' => [
                'type' => 'text',
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('consent_id');
        $this->forge->addKey('user_id');
        $this->forge->createTable('consent_records', true);
    }

    public function down()
    {
        $this->forge->dropTable('consent_records', true);
    }
}
