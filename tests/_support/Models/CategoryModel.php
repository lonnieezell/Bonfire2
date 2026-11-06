<?php

namespace Tests\Support\Models;

use CodeIgniter\Model;
use Tests\Support\Entities\Category;

class CategoryModel extends Model
{
    protected $table          = 'categories';
    protected $primaryKey     = 'id';
    protected $returnType     = Category::class;
    protected $allowedFields  = ['name', 'status', 'deleted_at'];
    protected $useSoftDeletes = true;
}
