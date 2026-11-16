<?php

namespace Bonfire\Media\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The media library: one row per uploaded file.
 */
class CreateMediaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'varchar', 'constraint' => 36],
            'container'  => ['type' => 'varchar', 'constraint' => 100],
            'folder'     => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
            'path'       => ['type' => 'varchar', 'constraint' => 255],
            'filename'   => ['type' => 'varchar', 'constraint' => 255],
            'mime'       => ['type' => 'varchar', 'constraint' => 255],
            'size'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'width'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'height'     => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'alt'        => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
            'deleted_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['container', 'path']);
        $this->forge->addKey('folder');
        $this->forge->createTable('media', true);
    }

    public function down()
    {
        $this->forge->dropTable('media', true);
    }
}
