<?php

namespace Bonfire\Content\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The content index. Files are the source of truth, so `spark content:reindex --fresh`
 * can always rebuild these tables.
 */
class CreateContentTables extends Migration
{
    public function up()
    {
        // TEXT stops at 64KB on MySQL, and a long post can pass that.
        $longText = $this->db->DBDriver === 'MySQLi' ? 'mediumtext' : 'text';

        $this->forge->addField([
            'id'           => ['type' => 'varchar', 'constraint' => 36],
            'collection'   => ['type' => 'varchar', 'constraint' => 100],
            'blueprint'    => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'slug'         => ['type' => 'varchar', 'constraint' => 255],
            'uri'          => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'parent_id'    => ['type' => 'varchar', 'constraint' => 36, 'null' => true],
            'sort'         => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'status'       => ['type' => 'varchar', 'constraint' => 10],
            'published_at' => ['type' => 'datetime', 'null' => true],
            'title'        => ['type' => 'varchar', 'constraint' => 255],
            'data'         => ['type' => $longText],
            'body'         => ['type' => $longText],
            'path'         => ['type' => 'varchar', 'constraint' => 255],
            'hash'         => ['type' => 'varchar', 'constraint' => 64],
            'mtime'        => ['type' => 'int', 'constraint' => 11],
            'deleted_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['collection', 'status', 'published_at']);
        $this->forge->addKey('uri');
        $this->forge->addKey('parent_id');
        $this->forge->addKey('path');
        $this->forge->createTable('content_entries', true);

        $this->forge->addField([
            'id'       => ['type' => 'varchar', 'constraint' => 36],
            'taxonomy' => ['type' => 'varchar', 'constraint' => 100],
            'slug'     => ['type' => 'varchar', 'constraint' => 255],
            'title'    => ['type' => 'varchar', 'constraint' => 255],
            'data'     => ['type' => 'text'],
            'path'     => ['type' => 'varchar', 'constraint' => 255],
            'hash'     => ['type' => 'varchar', 'constraint' => 64],
            'mtime'    => ['type' => 'int', 'constraint' => 11],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['taxonomy', 'slug']);
        $this->forge->createTable('content_terms', true);

        $this->forge->addField([
            'entry_id' => ['type' => 'varchar', 'constraint' => 36],
            'term_id'  => ['type' => 'varchar', 'constraint' => 36],
        ]);
        $this->forge->addPrimaryKey(['entry_id', 'term_id']);
        $this->forge->addKey('term_id');
        $this->forge->createTable('content_entry_terms', true);
    }

    public function down()
    {
        $this->forge->dropTable('content_entry_terms', true);
        $this->forge->dropTable('content_terms', true);
        $this->forge->dropTable('content_entries', true);
    }
}
