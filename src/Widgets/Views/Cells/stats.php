<div class="bf-stats-grid">
	<?php foreach ($stats  as $elem) : ?>

		<?php foreach ($elem->items() as $widget) : ?>

			<?= $this->setData(['stat' => $widget, 'showLink' => true])->include('Bonfire\Widgets\Views\Cells\_stat') ?>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
