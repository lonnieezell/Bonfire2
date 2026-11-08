<@php

namespace <?= $namespace ?>\Config;

class Registrar
{
    /**
     * The module's permissions, and the groups that get them when
     * php spark bonfire:permissions runs.
     */
    public static function AuthGroups(): array
    {
        return [
            'permissions' => [
<?php foreach ($permissions as $permission => $description) : ?>
                '<?= $permission ?>' => '<?= $description ?>',
<?php endforeach ?>
            ],
            'moduleGrants' => [
                '<?= $slug ?>' => [
                    'superadmin' => ['<?= $slug ?>.*'],
                    'admin'      => ['<?= implode("', '", array_keys($permissions)) ?>'],
                ],
            ],
        ];
    }
}
