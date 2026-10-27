<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<?= view('Bonfire\Widgets\Views\_chart_form', ['tab' => $tab, 'prefix' => 'line', 'config' => 'LineChart', 'title' => 'Line Chart', 'tension' => true]) ?>
<?php $this->endSection() ?>
