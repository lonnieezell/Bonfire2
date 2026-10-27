<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<?= view('Bonfire\Widgets\Views\_chart_form', ['tab' => $tab, 'prefix' => 'bar', 'config' => 'BarChart', 'title' => 'Bar Chart', 'schemed' => true]) ?>
<?php $this->endSection() ?>
