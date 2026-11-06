<?php

/**
 * The create and edit form of a resource. A form.php beside the resource's class replaces this view.
 *
 * @var Bonfire\Resources\Resource $resource
 * @var mixed                      $record   the record being edited, or null for a new one
 * @var string                     $action   where the form posts to
 * @var string                     $backUrl  the resource's list
 * @var list<array>                $sections what Bonfire\Fields\Form::sections() returns
 * @var list<string>               $notices  errors of the model that no input shows
 */
$this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc($record === null ? lang('Resources.newRecord') : lang('Resources.editing')) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<x-page-head back="<?= esc($backUrl, 'attr') ?>" backLabel="<?= esc($resource->title(), 'attr') ?>" title="<?= esc($record === null ? lang('Resources.newRecord') : lang('Resources.editing'), 'attr') ?>" />

<?php if ($record !== null) : ?>
    <x-tabs>
        <li class="nav-item">
            <a class="nav-link active" href="<?= esc($action, 'attr') ?>"><?= lang('Resources.details') ?></a>
        </li>
        <?= service('resourceTabs')->renderTabsFor($resource->slug) ?>
    </x-tabs>
<?php endif ?>

<?php foreach ($notices as $notice) : ?>
    <div class="alert alert-danger" role="alert" data-notice><?= esc($notice) ?></div>
<?php endforeach ?>

<form action="<?= esc($action, 'attr') ?>" method="post" data-resource-form="<?= esc($resource->slug, 'attr') ?>">
    <?= csrf_field() ?>

    <x-admin-box>
        <?= view('Bonfire\Fields\Views\form_fields', ['sections' => $sections], ['saveData' => false]) ?>
    </x-admin-box>

    <x-save-bar />
</form>
<?php $this->endSection() ?>
