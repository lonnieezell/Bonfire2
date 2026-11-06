<?php

namespace Tests\Support\Modules\Products\Catalog;

use Tests\Support\Modules\Products\ProductResource;

/**
 * The same products under another slug, with a form.php beside it that replaces the package form.
 */
class CatalogResource extends ProductResource
{
    public string $slug = 'catalog';
}
