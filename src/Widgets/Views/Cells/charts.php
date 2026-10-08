<div class="dashboard-cell-container row">
	<?php foreach ($charts  as $elem) : ?>

		<?php foreach ($elem->items() as $widget) : ?>

			<?php if ($widget->settings()->enabled()) : ?>
                <div class="<?= $widget->cssClass() ?>">
                    <canvas id="<?= $widget->chartName() ?>" class="chart-border"></canvas>
                </div>
			<?php endif?>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
