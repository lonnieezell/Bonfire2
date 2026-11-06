<?php

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property bool        $featured
 * @property int         $id
 * @property string      $name
 * @property string|null $price
 * @property string|null $sku
 * @property int|null    $stock
 */
class Product extends Entity
{
    protected $casts = ['featured' => 'bool'];
}
