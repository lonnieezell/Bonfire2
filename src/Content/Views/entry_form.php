<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= $entry === null ? lang('Content.newEntry') : esc($entry->title) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<?php $action = $entry === null ? url_to('content-new', $collection->handle) : url_to('content-edit', $collection->handle, $entry->id) ?>
<x-page-head back="<?= url_to('content-list', $collection->handle) ?>" backLabel="<?= esc($collection->title, 'attr') ?>" title="<?= esc(lang($entry === null ? 'Content.newEntry' : 'Content.editEntry'), 'attr') ?>" />

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
        <?php foreach ($rows as $row) : ?>
            <?php $field = $row['field'] ?>
            <div class="form-group mb-3" data-field="<?= esc($field->handle, 'attr') ?>">
                <label for="field-<?= esc($field->handle, 'attr') ?>" class="form-label"><?= esc($field->label) ?></label>
                <?= view($row['view'], [
                    'field'    => $field,
                    'name'     => 'fields[' . $field->handle . ']',
                    'id'       => 'field-' . $field->handle,
                    'value'    => $row['value'],
                    'disabled' => $row['disabled'],
                    'isNew'    => $entry === null,
                ], ['saveData' => false]) ?>
                <?php if ($field->instructions !== null) : ?>
                    <div class="form-text"><?= esc($field->instructions) ?></div>
                <?php endif ?>
                <?php if ($row['error'] !== null) : ?>
                    <p class="text-danger" data-error="<?= esc($field->handle, 'attr') ?>"><?= esc($row['error']) ?></p>
                <?php endif ?>
            </div>
        <?php endforeach ?>
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
