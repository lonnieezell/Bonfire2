<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Fields\Config;

use Bonfire\Fields\FieldType;
use CodeIgniter\Config\BaseConfig;

class Fields extends BaseConfig
{
    /**
     * Field types of your own, for blueprints and any other form to use: the handle, then a class that extends
     * Bonfire\Fields\FieldType. A handle that a built-in type has is taken over by yours.
     *
     * @var array<string, class-string<FieldType>>
     */
    public array $types = [];

    /**
     * Field types that other Bonfire modules add, such as Content's. Bonfire\Config\Registrar fills it in,
     * so leave it alone and put your own in $types.
     *
     * @var array<string, class-string<FieldType>>
     */
    public array $moduleTypes = [];
}
