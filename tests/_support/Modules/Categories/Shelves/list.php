<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<div data-custom-list="<?= esc($resource->slug, 'attr') ?>">
    <?= $this->include('Bonfire\Resources\Views\_table') ?>
</div>
<?php $this->endSection() ?>
