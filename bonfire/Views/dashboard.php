<?php $this->extend('master') ?>

<?php $this->section('styles') ?>

<?= asset_link('admin/css/widgets.css', 'css'); ?>

<?php $this->endSection() ?>


<?php $this->section('main') ?>
<h1>Home sweet home</h1>

<?= view('Bonfire\Views\Widgets\_stats', [
    'stats'   => $widgets->widget('stats')->items(),
    'manager' => $manager,
]) ?>
<?= view('Bonfire\Views\Widgets\_charts', [
    'charts'  => $widgets->widget('charts')->items(),
    'manager' => $manager,
]) ?>
<?php $this->endSection() ?>


<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/d3-color@3"></script>
<script src="https://cdn.jsdelivr.net/npm/d3-interpolate@3"></script>
<script src="https://cdn.jsdelivr.net/npm/d3-scale-chromatic@3"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.6.2/chart.min.js" integrity="sha512-tMabqarPtykgDtdtSqCL3uLVM0gS1ZkUAVhRFu1vSEFgvB73niFQWJuvviDyBGBH22Lcau4rHB5p2K2T0Xvr6Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<?= asset_link('admin/js/chart.js', 'js'); ?>

<script>
	<?php foreach ($widgets->widget('charts')->items()  as $elem) : ?>

	<?php foreach ($elem->items() as $index => $widget) : ?>

	<?php
    $_widgets = array_values(
    array_filter($manager, static function ($k) {
        return $k['widget'] === 'Charts';
    }, ARRAY_FILTER_USE_BOTH)
);

    ?>
	<?php if (setting('Stats.' . $_widgets[$index]['widget'] . '_' . $index)) : ?>
	<?= $widget->getScript(); ?>
	<?php endif?>

	<?php endforeach; ?>

	<?php endforeach; ?>

</script>
<?php $this->endSection() ?>
