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
                '<?= $slug ?>.list'   => 'Can view the list of <?= $plural ?>',
                '<?= $slug ?>.create' => 'Can create <?= $plural ?>',
                '<?= $slug ?>.edit'   => 'Can edit <?= $plural ?>',
                '<?= $slug ?>.delete' => 'Can delete <?= $plural ?>',
            ],
            'moduleGrants' => [
                '<?= $slug ?>' => [
                    'superadmin' => ['<?= $slug ?>.*'],
                    'admin'      => ['<?= $slug ?>.list', '<?= $slug ?>.create', '<?= $slug ?>.edit', '<?= $slug ?>.delete'],
                ],
            ],
        ];
    }
}
