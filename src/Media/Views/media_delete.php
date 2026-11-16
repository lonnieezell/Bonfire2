<?php
/**
 * Asks, then sends a media file to the Recycler. Its own form, so that it can't be mistaken for saving.
 *
 * @var Bonfire\Media\Entities\Media $media
 * @var string                       $size   an extra button class, such as btn-sm
 * @var string                       $return the list's query string to go back to
 */
?>
<form action="<?= url_to('media-delete', $media->id) ?>" method="post" class="d-inline-block">
    <?= csrf_field() ?>
    <input type="hidden" name="return" value="<?= esc($return, 'attr') ?>">
    <button type="submit" class="btn <?= $size ?> btn-outline-danger" data-delete
        data-confirm="<?= esc(lang('Media.deleteConfirm'), 'attr') ?>"
        data-confirm-body="<?= esc(lang('Media.deleteConfirmBody'), 'attr') ?>"
        data-confirm-button="<?= esc(lang('Bonfire.delete'), 'attr') ?>"
        data-confirm-danger="1"><?= lang('Bonfire.delete') ?></button>
</form>
