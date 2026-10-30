<@php
$actionList = service('actions')->for('<?= $slug ?>')->showing($<?= $listVar ?>);
$selectable = $actionList->selectable(auth()->user());
$canEdit    = auth()->user()->can('<?= $slug ?>.edit');
?>
<@php if ($<?= $listVar ?> === []) : ?>
    <x-empty-state title="No <?= $plural ?> match" hint="Try a different search, or add a <?= $singular ?>." />
<@php else : ?>
    <form method="post">
        <@= csrf_field() ?>
        <div class="table-responsive">
            <table class="table table-hover bf-table">
                <thead>
                    <tr>
                        <@php if ($selectable) : ?>
                            <th style="width: 3em"><input type="checkbox" class="form-check select-all" aria-label="Select all"></th>
                        <@php endif ?>
<?php foreach ($listFields as $field) : ?>
                        <th><?= humanize($field) ?></th>
<?php endforeach ?>
                        <th class="text-end"><@= lang('Bonfire.actionsCol') ?></th>
                    </tr>
                </thead>
                <tbody>
                <@php foreach ($<?= $listVar ?> as $<?= $var ?>) : ?>
                    <tr>
                        <@php if ($selectable) : ?>
                            <td class="bf-select-cell">
                                <input type="checkbox" name="selects[]" value="<@= esc($<?= $var ?>-><?= $primaryKey ?>) ?>" class="form-check-input" aria-label="Select"
                                    <@= $actionList->selectable(auth()->user(), $<?= $var ?>) ? '' : 'disabled' ?>>
                            </td>
                        <@php endif ?>
<?php foreach ($listFields as $i => $field) : ?>
<?php if ($i === 0) : ?>
                        <td>
                            <@php if ($canEdit) : ?>
                                <a href="<@= url_to('<?= $slug ?>-edit', $<?= $var ?>-><?= $primaryKey ?>) ?>"><@= esc($<?= $var ?>-><?= $field ?>) ?></a>
                            <@php else : ?>
                                <@= esc($<?= $var ?>-><?= $field ?>) ?>
                            <@php endif ?>
                        </td>
<?php else : ?>
                        <td><@= esc($<?= $var ?>-><?= $field ?>) ?></td>
<?php endif ?>
<?php endforeach ?>
                        <td class="text-end">
                            <x-row-actions list="<?= $slug ?>" id="<@= esc($<?= $var ?>-><?= $primaryKey ?>) ?>">
                                <@php if ($canEdit) : ?>
                                    <li><a href="<@= url_to('<?= $slug ?>-edit', $<?= $var ?>-><?= $primaryKey ?>) ?>" class="dropdown-item"><@= lang('Bonfire.edit') ?></a></li>
                                <@php endif ?>
                            </x-row-actions>
                        </td>
                    </tr>
                <@php endforeach ?>
                </tbody>
            </table>
        </div>

        <@php if ($selectable) : ?>
            <x-selection-bar>
                <x-bulk-actions list="<?= $slug ?>" />
            </x-selection-bar>
        <@php endif ?>
    </form>

    <@= $pager->links('default', 'bonfire_list_hx') ?>
<@php endif ?>
