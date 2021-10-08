<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMetaTable extends Migration
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
           'resource_id' => [
                'type' => 'int',
               'constraint' => 11,
               'unsigned' => true,
           ],
           'class' => [
               'type' => 'varchar',
               'constraint' => 255,
           ],
           'key' => [
               'type' => 'varchar',
               'constraint' => 255,
           ],
           'value' => [
               'type' => 'text',
               'null' => true
           ],
           'created_at' => [
               'type' => 'datetime',
               'null' => false,
           ],
           'updated_at' => [
               'type' => 'datetime',
               'null' => false,
           ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('resource_id');    // It's too generic to FK
        $this->forge->createTable('meta_info', true);
    }

    public function down()
    {
        $this->forge->dropTable('meta_info', true);
    }
}
