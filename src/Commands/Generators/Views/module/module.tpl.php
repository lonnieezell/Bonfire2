<@php

namespace <?= $namespace ?>;

<?php
$uses = ['Bonfire\Actions\ModelSource', 'Bonfire\Core\BaseModule', 'Bonfire\Menus\MenuItem', "{$namespace}\\Actions\\{$action}", $modelClass];
sort($uses);
?>
<?php foreach ($uses as $use) : ?>
use <?= $use ?>;
<?php endforeach ?>

class Module extends BaseModule
{
    public function initAdmin()
    {
        $item = new MenuItem([
            'title'           => '<?= $title ?>',
            'namedRoute'      => '<?= $slug ?>-list',
            'fontAwesomeIcon' => 'fas fa-list',
            'permission'      => '<?= $slug ?>.list',
        ]);
        service('menus')->menu('sidebar')->collection('content')->addItem($item);

        service('actions')->for('<?= $slug ?>')
            ->records(new ModelSource(<?= $modelShort ?>::class))
            ->add(new <?= $action ?>());
<?php if ($recycler !== null) : ?>

        service('recycler')->register('<?= $slug ?>', '<?= $title ?>', <?= $modelShort ?>::class, ['<?= implode("', '", $recycler) ?>']);
<?php endif ?>
    }
}
