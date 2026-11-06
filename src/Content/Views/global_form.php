<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc($blueprint->title) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<?php $action = url_to('content-global', $set->handle) ?>
<x-page-head title="<?= esc($blueprint->title, 'attr') ?>" />

<?php if ($notice !== null) : ?>
    <div class="alert alert-danger" role="alert" data-notice>
        <?= esc($notice) ?>
        <?php if ($conflict) : ?>
            <a href="<?= $action ?>" data-reload><?= lang('Content.reloadGlobal') ?></a>
        <?php endif ?>
    </div>
<?php endif ?>

<form action="<?= $action ?>" method="post" data-global-form>
    <?= csrf_field() ?>
    <input type="hidden" name="hash" value="<?= esc($hash, 'attr') ?>">

    <x-admin-box>
        <?= view('Bonfire\Fields\Views\form_fields', ['rows' => $rows], ['saveData' => false]) ?>
    </x-admin-box>

    <x-save-bar save="<?= esc(lang('Content.saveGlobal'), 'attr') ?>" />
</form>
<?php $this->endSection() ?>
