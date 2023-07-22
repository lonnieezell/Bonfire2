<?php $this->extend('master') ?>

<?php $this->section('styles') ?>

	<?= asset_link('admin/css/widgets.css', 'css'); ?>

<?php $this->endSection() ?>


<?php $this->section('main') ?>

	<?= $cells->render(); ?>

<?php $this->endSection() ?>
