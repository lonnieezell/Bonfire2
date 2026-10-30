<@php

namespace <?= $namespace ?>\Actions;

<?php
$uses = ['Bonfire\Actions\DeleteAction', 'RuntimeException', $modelClass];
sort($uses);
?>
<?php foreach ($uses as $use) : ?>
use <?= $use ?>;
<?php endforeach ?>

/**
 * Deletes <?= $plural ?> from the list's row menu and selection bar.
 */
class <?= $action ?> extends DeleteAction
{
    public string $permission = '<?= $slug ?>.delete';

    protected function delete(array $records): void
    {
        $model = model(<?= $modelShort ?>::class);

        if (! $model->delete(array_map(static fn ($record) => $record-><?= $primaryKey ?>, $records))) {
            throw new RuntimeException(implode(' ', $model->errors()));
        }
    }

    protected function noun(): string
    {
        return '<?= $plural ?>';
    }
}
