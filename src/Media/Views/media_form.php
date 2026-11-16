<?php
/**
 * A media file's details, and the form for its alt text and folder.
 *
 * @var Bonfire\Media\Entities\Media $media
 * @var array<string, string>        $errors
 * @var bool                         $canDelete
 */
?>
<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc($media->filename) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<x-page-head back="<?= url_to('media-list') ?>?folder=<?= esc(rawurlencode($media->folder), 'attr') ?>" backLabel="<?= esc(lang('Media.modTitle'), 'attr') ?>" title="<?= esc(lang('Media.editFile'), 'attr') ?>">
    <?php if ($canDelete) : ?>
        <?= view('Bonfire\Media\Views\media_delete', ['media' => $media, 'size' => '', 'return' => http_build_query(['folder' => $media->folder])], ['saveData' => false]) ?>
    <?php endif ?>
</x-page-head>

<div class="row g-3">
    <div class="col-md-5">
        <x-admin-box>
            <?php if ($media->isImage()) : ?>
                <img src="<?= esc($media->url(), 'attr') ?>" alt="<?= esc($media->alt ?? '', 'attr') ?>" class="img-fluid mb-3">
            <?php endif ?>
            <dl class="mb-0">
                <dt><?= lang('Media.filename') ?></dt>
                <dd class="text-break"><?= esc($media->filename) ?></dd>
                <dt><?= lang('Media.url') ?></dt>
                <dd class="text-break"><a href="<?= esc($media->url(), 'attr') ?>" target="_blank" rel="noopener"><?= esc($media->url()) ?></a></dd>
                <dt><?= lang('Media.type') ?></dt>
                <dd><?= esc($media->mime) ?></dd>
                <dt><?= lang('Media.size') ?></dt>
                <dd><?= number_to_size($media->size) ?></dd>
                <?php if ($media->isImage()) : ?>
                    <dt><?= lang('Media.dimensions') ?></dt>
                    <dd><?= $media->width ?> × <?= $media->height ?></dd>
                <?php endif ?>
            </dl>
        </x-admin-box>
    </div>

    <div class="col-md-7">
        <form action="<?= url_to('media-edit', $media->id) ?>" method="post" data-media-form>
            <?= csrf_field() ?>

            <x-admin-box>
                <div class="mb-3">
                    <label for="alt" class="form-label"><?= lang('Media.alt') ?></label>
                    <input type="text" name="alt" id="alt" class="form-control<?= isset($errors['alt']) ? ' is-invalid' : '' ?>" value="<?= esc(old('alt', $media->alt ?? ''), 'attr') ?>" maxlength="255">
                    <div class="form-text"><?= lang('Media.altHint') ?></div>
                    <?php if (isset($errors['alt'])) : ?>
                        <div class="invalid-feedback"><?= esc($errors['alt']) ?></div>
                    <?php endif ?>
                </div>
                <div class="mb-3">
                    <label for="folder" class="form-label"><?= lang('Media.folder') ?></label>
                    <input type="text" name="folder" id="folder" class="form-control<?= isset($errors['folder']) ? ' is-invalid' : '' ?>" value="<?= esc(old('folder', $media->folder), 'attr') ?>" maxlength="255">
                    <div class="form-text"><?= lang('Media.folderHint') ?></div>
                    <?php if (isset($errors['folder'])) : ?>
                        <div class="invalid-feedback"><?= esc($errors['folder']) ?></div>
                    <?php endif ?>
                </div>
            </x-admin-box>

            <x-save-bar save="<?= esc(lang('Media.saveFile'), 'attr') ?>" />
        </form>
    </div>
</div>
<?php $this->endSection() ?>
