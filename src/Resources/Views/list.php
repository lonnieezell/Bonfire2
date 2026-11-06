<?php

/**
 * The list of a resource. A list.php beside the resource's class replaces this view.
 *
 * @var Bonfire\Resources\Resource $resource
 * @var int                        $total
 * @var bool                       $canCreate whether to offer the new record button
 */
$this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= esc($resource->title(), 'attr') ?>" count="<?= $total ?>">
    <?php if ($canCreate) : ?>
        <a href="<?= url_to($resource->slug . '-new') ?>" class="btn btn-primary" data-new-record><i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Resources.new') ?></a>
    <?php endif ?>
</x-page-head>

<x-admin-box>
    <div id="content-list" hx-target="#content-list" data-resource-list="<?= esc($resource->slug, 'attr') ?>">
        <?= $this->include('Bonfire\Resources\Views\_table') ?>
    </div>
</x-admin-box>
<?php $this->endSection() ?>
