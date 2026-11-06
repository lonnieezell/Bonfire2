<?php

/**
 * A form that replaces the package's: it has the same variables.
 *
 * @var Bonfire\Resources\Resource $resource
 * @var string                     $action
 */
$this->extend('master') ?>

<?php $this->section('main') ?>
<form action="<?= esc($action, 'attr') ?>" method="post" data-custom-form="<?= esc($resource->slug, 'attr') ?>">
    <?= csrf_field() ?>
    <?= view('Bonfire\Fields\Views\form_fields', ['sections' => $sections], ['saveData' => false]) ?>
    <x-save-bar />
</form>
<?php $this->endSection() ?>
