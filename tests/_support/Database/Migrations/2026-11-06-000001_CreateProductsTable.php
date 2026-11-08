<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The table of the Products resource that the tests use. It has a column for each v1 field type.
 */
class CreateProductsTable extends Migration
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
            'name' => [
                'type'       => 'varchar',
                'constraint' => 255,
            ],
            'slug' => [
                'type'       => 'varchar',
                'constraint' => 255,
                'null'       => true,
            ],
            'sku' => [
                'type'       => 'varchar',
                'constraint' => 64,
                'null'       => true,
            ],
            'summary' => [
                'type' => 'text',
                'null' => true,
            ],
            'description' => [
                'type' => 'text',
                'null' => true,
            ],
            'price' => [
                'type'       => 'decimal',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'stock' => [
                'type'       => 'int',
                'constraint' => 11,
                'null'       => true,
            ],
            'category_id' => [
                'type'       => 'int',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'varchar',
                'constraint' => 20,
                'default'    => 'draft',
            ],
            'featured' => [
                'type'       => 'tinyint',
                'constraint' => 1,
                'default'    => 0,
            ],
            'released_on' => [
                'type' => 'date',
                'null' => true,
            ],
            'available_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('products', true);
    }

    public function down()
    {
        $this->forge->dropTable('products', true);
    }
}
