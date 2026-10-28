<?php
/**
 * A row's menu: the slot (links like Edit) and then the row actions of list $list for record $id
 * that the admin may run on that record. They submit the form <x-confirm-dialog> puts in the
 * layout, so they work inside another form.
 */
$actionList = service('actions')->for($list);
$actions    = $actionList->row(auth()->user());

// A component only gets strings, so it loads the record to ask each action's allows().
if ($actions !== []) {
    $record  = $actionList->source()->find([$id])[0] ?? null;
    $actions = $record === null ? [] : $actionList->row(auth()->user(), $record);
}
$hasSlot = trim($slot ?? '') !== '';
?>
<?php if ($actions !== [] || $hasSlot) : ?>
<div class="dropdown">
    <?php // Fixed positioning, so the scrolling .table-responsive around the list doesn't crop the menu. ?>
    <button class="bf-icon-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="<?= lang('Bonfire.actionsCol') ?>">
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
