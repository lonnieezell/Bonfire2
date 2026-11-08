<?php

namespace Tests\Support\Models;

use CodeIgniter\Model;

/**
 * A model over a table with a column of each type the resource generator maps. Its table is created by
 * ResourceGeneratorTest with the declared types written out, which the forge leaves out on SQLite.
 */
class TypeSampleModel extends Model
{
    protected $table         = 'type_samples';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'title', 'note', 'body', 'qty', 'price', 'active', 'level', 'starts_on', 'starts_at', 'seen_at', 'owner_id', 'parent_id',
    ];
}
