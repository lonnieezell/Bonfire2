<@php

namespace <?= $namespace ?>;

<?php foreach ($imports as $use) : ?>
use <?= $use ?>;
<?php endforeach ?>

class <?= $class ?> extends Resource
{
    public string $model      = <?= $modelShort ?>::class;
    public string $slug       = '<?= $slug ?>';
    public string $permission = '<?= $slug ?>';
    public array $pages       = ['<?= implode("', '", $pages) ?>'];

    public function fields(): array
    {
        return [
<?php foreach ($fields as $line) : ?>
            <?= $line ?>

<?php endforeach ?>
        ];
    }
}
