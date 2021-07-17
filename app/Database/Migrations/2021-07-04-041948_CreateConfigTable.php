<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateConfigTable extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([
           'class' => ['type' => 'varchar', 'constraint' => 255],
           'key' => ['type' => 'varchar', 'constraint' => 255],
           'value' => ['type' => 'text', 'null' => true],
           'created_at' => ['type' => 'datetime', 'null' => false],
           'updated_at' => ['type' => 'datetime', 'null' => false],
       ]);
        $this->forge->createTable(config('Config')->configTable);
    }

    public function down()
    {
        $this->forge->dropTable(config('Config')->configTable);
    }
}
