<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<?= view('Bonfire\Widgets\Views\_chart_form', ['tab' => $tab, 'prefix' => 'polarArea', 'config' => 'PolarAreaChart', 'title' => 'Polar Area Chart', 'schemed' => true]) ?>
<?php $this->endSection() ?>
