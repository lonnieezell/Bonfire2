<div class="bf-chart-grid">
	<?php foreach ($charts  as $elem) : ?>

		<?php foreach ($elem->items() as $widget) : ?>

            <div class="bf-chart <?= esc($widget->cssClass()) ?>">
				<?= $this->setData(['chart' => $widget])->include('Bonfire\Widgets\Views\Cells\_chart') ?>
            </div>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
