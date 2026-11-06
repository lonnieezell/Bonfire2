<?php

namespace Tests\Support\Modules\Categories\Shelves;

use Tests\Support\Modules\Categories\CategoryResource;

/**
 * Lists the same table as the Categories resource, with a list.php beside it that replaces the package's view.
 */
class ShelfResource extends CategoryResource
{
    public string $slug = 'shelves';
}
