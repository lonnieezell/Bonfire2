<?php

/**
 * The list of a resource. A list.php beside the resource's class replaces this view.
 *
 * @var Bonfire\Resources\Resource $resource
 * @var int                        $total
 */
$this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= esc($resource->title(), 'attr') ?>" count="<?= $total ?>" />

<x-admin-box>
    <div id="content-list" hx-target="#content-list" data-resource-list="<?= esc($resource->slug, 'attr') ?>">
        <?= $this->include('Bonfire\Resources\Views\_table') ?>
    </div>
</x-admin-box>
<?php $this->endSection() ?>
