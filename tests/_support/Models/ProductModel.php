<?php

namespace Tests\Support\Models;

use CodeIgniter\Model;
use Tests\Support\Entities\Product;

class ProductModel extends Model
{
    protected $table          = 'products';
    protected $primaryKey     = 'id';
    protected $returnType     = Product::class;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'name', 'slug', 'sku', 'summary', 'description', 'price', 'stock',
        'category_id', 'status', 'featured', 'released_on', 'available_at', 'deleted_at',
    ];
    protected $validationRules = [
        'name' => 'required|min_length[3]',
    ];
}
