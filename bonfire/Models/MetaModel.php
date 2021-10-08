<?php

namespace Bonfire\Models;

use CodeIgniter\Model;

class MetaModel extends Model
{
    protected $table = 'meta_info';
    protected $allowedFields = ['class', 'resource_id', 'key', 'value'];
    protected $returnType = 'object';
    protected $useTimestamps = true;
}
