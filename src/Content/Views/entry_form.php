<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= $entry === null ? lang('Content.newEntry') : esc($entry->title) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<?php $action = $entry === null ? url_to('content-new', $collection->handle) : url_to('content-edit', $collection->handle, $entry->id) ?>
<x-page-head back="<?= url_to('content-list', $collection->handle) ?>" backLabel="<?= esc($collection->title, 'attr') ?>" title="<?= esc(lang($entry === null ? 'Content.newEntry' : 'Content.editEntry'), 'attr') ?>">
    <?php if ($canPreview) : ?>
        <a href="<?= url_to('content-preview-link', $collection->handle, $entry->id) ?>" class="btn btn-outline-secondary" data-preview-link><i class="fas fa-eye" aria-hidden="true"></i> <?= lang('Content.preview') ?></a>
    <?php endif ?>
    <?php if ($canDelete) : ?>
        <?= view('Bonfire\Content\Views\entry_delete', ['handle' => $collection->handle, 'id' => $entry->id, 'below' => $below], ['saveData' => false]) ?>
    <?php endif ?>
</x-page-head>

<?php if ($notice !== null) : ?>
    <div class="alert alert-danger" role="alert" data-notice>
        <?= esc($notice) ?>
        <?php if ($conflict) : ?>
            <a href="<?= $action ?>" data-reload><?= lang('Content.reload') ?></a>
        <?php endif ?>
    </div>
<?php endif ?>

<form action="<?= $action ?>" method="post" data-entry-form>
    <?= csrf_field() ?>
    <input type="hidden" name="hash" value="<?= esc($hash, 'attr') ?>">
    <input type="hidden" name="blueprint" value="<?= esc($blueprint->handle, 'attr') ?>">

    <x-admin-box>
        <?= view('Bonfire\Content\Views\form_fields', ['rows' => $rows], ['saveData' => false]) ?>
    </x-admin-box>

    <x-admin-box title="<?= esc(lang('Content.publishing'), 'attr') ?>">
        <div class="form-group mb-3" data-field="status">
            <label for="status" class="form-label"><?= lang('Content.status') ?></label>
            <select name="status" id="status" class="form-select"<?= $canPublish ? '' : ' disabled' ?>>
                <?php foreach (['draft' => 'Content.statusDraft', 'published' => 'Content.statusPublished'] as $value => $label) : ?>
                    <option value="<?= $value ?>"<?= $status === $value ? ' selected' : '' ?>><?= lang($label) ?></option>
                <?php endforeach ?>
            </select>
            <?php if (isset($errors['status'])) : ?>
                <p class="text-danger" data-error="status"><?= esc($errors['status']) ?></p>
            <?php endif ?>
        </div>
        <div class="form-group" data-field="published_at">
            <label for="published_at" class="form-label"><?= lang('Content.publishedAt') ?></label>
            <input type="datetime-local" step="1" name="published_at" id="published_at" class="form-control"
                value="<?= esc(str_replace(' ', 'T', $publishedAt), 'attr') ?>"<?= $canPublish ? '' : ' disabled' ?>>
            <div class="form-text"><?= lang('Content.publishedAtHint') ?></div>
            <?php if (isset($errors['published_at'])) : ?>
                <p class="text-danger" data-error="published_at"><?= esc($errors['published_at']) ?></p>
            <?php endif ?>
        </div>
    </x-admin-box>

    <x-save-bar save="<?= esc(lang('Content.saveEntry'), 'attr') ?>" />
</form>
<?php $this->endSection() ?>
