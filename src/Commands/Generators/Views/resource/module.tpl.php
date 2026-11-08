<@php

namespace <?= $namespace ?>;

use Bonfire\Core\BaseModule;

class Module extends BaseModule
{
    public function initAdmin()
    {
        service('resources')->register(<?= $class ?>::class);
    }
}
