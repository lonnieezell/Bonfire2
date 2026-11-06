<?php

namespace Tests\Support\Models;

use CodeIgniter\Model;

/**
 * The same table as ProductModel, with records as arrays and no entity.
 */
class ProductArrayModel extends Model
{
    protected $table         = 'products';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'sku', 'status'];
}
