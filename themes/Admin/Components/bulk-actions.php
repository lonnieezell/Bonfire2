<?php
/**
 * The bulk actions of list $list, as buttons that post the checked selects[] of their form.
 * Place inside <x-selection-bar>.
 */
?>
<?php foreach (service('actions')->for($list)->bulk(auth()->user()) as $action) : ?>
    <button type="submit" formaction="<?= url_to('action', $list, $action->name()) ?>" data-confirm-bulk="1"
        class="btn <?= $action->danger ? 'btn-danger' : 'btn-outline-secondary' ?>"<?= $action->confirmAttributes() ?>>
        <?php if ($action->icon !== '') : ?><i class="<?= esc($action->icon) ?>" aria-hidden="true"></i><?php endif ?>
        <?= esc($action->label) ?>
    </button>
<?php endforeach ?>
