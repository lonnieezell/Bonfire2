<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<?= view('Bonfire\Widgets\Views\_chart_form', ['tab' => $tab, 'prefix' => 'doughnut', 'config' => 'DoughnutChart', 'title' => 'Doughnut Chart', 'schemed' => true]) ?>
<?php $this->endSection() ?>
