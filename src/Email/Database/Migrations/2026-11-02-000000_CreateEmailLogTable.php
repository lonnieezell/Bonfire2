<?php

namespace Bonfire\Email\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The Outbox: one row per send, with no message body.
 */
class CreateEmailLogTable extends Migration
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
            'mailer' => [
                'type'       => 'varchar',
                'constraint' => 100,
                'null'       => true,
            ],
            'transport' => [
                'type'       => 'varchar',
                'constraint' => 100,
                'null'       => true,
            ],
            'mailable_class' => [
                'type'       => 'varchar',
                'constraint' => 255,
                'null'       => true,
            ],
            'subject' => [
                'type' => 'text',
                'null' => true,
            ],
            'from_address' => [
                'type'       => 'varchar',
                'constraint' => 255,
                'null'       => true,
            ],
            'to' => [
                'type' => 'text',
                'null' => true,
            ],
            'cc' => [
                'type' => 'text',
                'null' => true,
            ],
            'bcc' => [
                'type' => 'text',
                'null' => true,
            ],
            'attachments' => [
                'type' => 'text',
                'null' => true,
            ],
            'status' => [
                'type'       => 'varchar',
                'constraint' => 10,
            ],
            'message_id' => [
                'type'       => 'varchar',
                'constraint' => 255,
                'null'       => true,
            ],
            'error' => [
                'type' => 'text',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('created_at');
        $this->forge->addKey('status');
        $this->forge->createTable('email_log', true);
    }

    public function down()
    {
        $this->forge->dropTable('email_log', true);
    }
}
