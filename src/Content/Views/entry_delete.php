<?php
/**
 * Asks, then moves an entry to the trash. Its own form, so that it can't be mistaken for saving.
 *
 * @var string $handle the collection
 * @var string $id
 * @var int    $below  how many pages below it go too, which the post repeats so the delete can be refused if that has changed
 */
?>
<form action="<?= url_to('content-delete', $handle, $id) ?>" method="post" class="d-inline-block">
    <?= csrf_field() ?>
    <input type="hidden" name="descendants" value="<?= $below ?>">
    <button type="submit" class="btn btn-sm btn-outline-danger" data-delete
        data-confirm="<?= esc(lang('Content.deleteConfirm'), 'attr') ?>"
        data-confirm-body="<?= esc($below > 0 ? lang('Content.deleteConfirmBelow', [$below]) : lang('Content.deleteConfirmBody'), 'attr') ?>"
        data-confirm-button="<?= esc(lang('Bonfire.delete'), 'attr') ?>"
        data-confirm-danger="1"><?= lang('Bonfire.delete') ?></button>
</form>
