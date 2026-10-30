<@php $this->extend('master') ?>

<@php $heading = isset($<?= $var ?>) ? 'Edit <?= $singular ?>' : 'New <?= $singular ?>' ?>

<@php $this->section('crumb') ?><@= $heading ?><@php $this->endSection() ?>

<@php $this->section('main') ?>
<x-page-head back="<@= url_to('<?= $slug ?>-list') ?>" backLabel="<?= $title ?>" title="<@= $heading ?>" />

<form action="<@= isset($<?= $var ?>) ? url_to('<?= $slug ?>-save', $<?= $var ?>-><?= $primaryKey ?>) : url_to('<?= $slug ?>-create') ?>" method="post">
    <@= csrf_field() ?>

    <x-admin-box>
<?php foreach ($fields as $field) : ?>
        <div class="form-group">
            <label for="<?= $field ?>" class="form-label"><?= humanize($field) ?></label>
            <input type="text" name="<?= $field ?>" id="<?= $field ?>" class="form-control" value="<@= esc(old('<?= $field ?>', $<?= $var ?>-><?= $field ?> ?? '')) ?>">
            <@php if (has_error('<?= $field ?>')) : ?>
                <p class="text-danger"><@= error('<?= $field ?>') ?></p>
            <@php endif ?>
        </div>
<?php endforeach ?>
    </x-admin-box>

    <x-save-bar />
</form>
<@php $this->endSection() ?>
