<?php

namespace Tests\Support\Models;

use CodeIgniter\Model;

/**
 * The model ModuleGeneratorTest generates a module for. Its table is
 * created by the test.
 */
class ArticleModel extends Model
{
    protected $table           = 'articles';
    protected $returnType      = 'object';
    protected $useSoftDeletes  = true;
    protected $useTimestamps   = true;
    protected $allowedFields   = ['title', 'summary', 'deleted_at'];
    protected $validationRules = ['title' => 'required|max_length[200]'];
}
