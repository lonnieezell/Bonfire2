<div class="bf-chart-grid">
	<?php foreach ($charts  as $elem) : ?>

		<?php foreach ($elem->items() as $widget) : ?>
		<?php if ($widget->permission() !== null && ! auth()->user()?->can($widget->permission())) { continue; } ?>

            <div class="bf-chart <?= esc($widget->cssClass()) ?>">
				<?= $this->setData(['chart' => $widget])->include('Bonfire\Widgets\Views\Cells\_chart') ?>
            </div>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
