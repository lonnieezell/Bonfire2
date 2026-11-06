<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= $term === null ? lang('Content.newTerm') : esc((string) $term['title']) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<?php $action = $term === null ? url_to('content-term-new', $taxonomy->handle) : url_to('content-term-edit', $taxonomy->handle, (string) $term['slug']) ?>
<x-page-head back="<?= url_to('content-terms', $taxonomy->handle) ?>" backLabel="<?= esc($taxonomy->title, 'attr') ?>" title="<?= esc(lang($term === null ? 'Content.newTerm' : 'Content.editTerm'), 'attr') ?>" />

<?php if ($notice !== null) : ?>
    <div class="alert alert-danger" role="alert" data-notice><?= esc($notice) ?></div>
<?php endif ?>

<form action="<?= $action ?>" method="post" data-term-form>
    <?= csrf_field() ?>

    <x-admin-box>
        <?= view('Bonfire\Fields\Views\form_fields', ['rows' => $rows], ['saveData' => false]) ?>
    </x-admin-box>

    <x-save-bar save="<?= esc(lang('Content.saveTerm'), 'attr') ?>" />
</form>
<?php $this->endSection() ?>
