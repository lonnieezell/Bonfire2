<?php

namespace Tests\Support\Packages\Courier\Config;

/**
 * How a Composer package registers its module with Bonfire.
 * Not auto-discovered here, since it isn't at a namespace root; tests add it.
 */
class Registrar
{
    public static function Bonfire(): array
    {
        return ['packageModules' => ['Tests\Support\Packages\Courier' => realpath(__DIR__ . '/..')]];
    }

    public static function AuthGroups(): array
    {
        return [
            'permissions' => [
                'courier.send' => 'Can send couriers',
            ],
            'moduleGrants' => [
                'courier' => [
                    'superadmin' => ['courier.*'],
                    'admin'      => ['courier.send'],
                ],
            ],
        ];
    }
}
