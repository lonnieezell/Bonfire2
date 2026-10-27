<div class="bf-chart-grid">
	<?php foreach ($charts  as $elem) : ?>

		<?php foreach ($elem->items() as $widget) : ?>

			<?php if ($widget->settings()->enabled()) : ?>
                <div class="bf-chart <?= esc($widget->cssClass()) ?>">
                    <x-admin-box title="<?= esc($widget->title()) ?>">
                        <canvas id="<?= $widget->chartName() ?>" class="chart-border"></canvas>
                    </x-admin-box>
                </div>
			<?php endif?>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
