<div class="dashboard-cell-container row">
	<?php foreach ($charts  as $elem) : ?>

		<?php foreach ($elem->items() as $index => $widget) : ?>

			<?php
            $_widgets = array_values(
                array_filter($manager, static fn ($k) => $k['widget'] === 'Charts', ARRAY_FILTER_USE_BOTH)
            );
		    ?>

			<?php if (setting('Stats.' . $_widgets[$index]['widget'] . '_' . $index)) : ?>
                <div class="<?= $widget->cssClass() ?>">
                    <canvas id="<?= $widget->chartName() ?>" class="chart-border"></canvas>
                </div>
			<?php endif?>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
