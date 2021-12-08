<?php $this->extend('master') ?>

<?php $this->section('styles') ?>

<?= asset_link( 'admin/css/widgets.css', 'css'); ?>

<?php $this->endSection() ?>


<?php $this->section('main') ?>
	<h1>Home sweet home</h1>

    <?= view('Bonfire\Views\Widgets\_stats', ['stats' => $widgets->widget('stats')->items()]) ?>

<?php $this->endSection() ?>
