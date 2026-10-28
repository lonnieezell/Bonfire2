<?php
/**
 * A row's menu: the slot (links like Edit) and then the row actions of list $list for record $id.
 * Actions are shown by permission; an action's allows() is checked when it runs.
 * They submit the form <x-confirm-dialog> puts in the layout, so they work inside another form.
 */
$actions = service('actions')->for($list)->row(auth()->user());
$hasSlot = trim($slot ?? '') !== '';
?>
<?php if ($actions !== [] || $hasSlot) : ?>
<div class="dropdown">
    <button class="bf-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= lang('Bonfire.actionsCol') ?>">
        <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end bf-row-menu">
        <?= $slot ?? '' ?>
        <?php foreach ($actions as $i => $action) : ?>
            <?php if ($action->danger && ($i > 0 || $hasSlot)) : ?>
                <li><hr class="dropdown-divider"></li>
            <?php endif ?>
            <li>
                <button type="submit" form="bf-action-form" formaction="<?= url_to('action', $list, $action->name()) ?>" name="selects[]" value="<?= esc($id) ?>"
                    class="dropdown-item<?= $action->danger ? ' text-danger' : '' ?>"<?= $action->confirmAttributes() ?>>
                    <?php if ($action->icon !== '') : ?><i class="<?= esc($action->icon) ?>" aria-hidden="true"></i><?php endif ?>
                    <?= esc($action->label) ?>
                </button>
            </li>
        <?php endforeach ?>
    </ul>
</div>
<?php endif ?>
