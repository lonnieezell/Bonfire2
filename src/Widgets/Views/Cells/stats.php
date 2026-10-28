<div class="bf-stats-grid">
	<?php foreach ($stats  as $elem) : ?>

		<?php foreach ($elem->items() as $widget) : ?>

			<?php if ($widget->settings()->enabled()) : ?>
				<?= $this->setData(['stat' => $widget])->include('Bonfire\Widgets\Views\Cells\_stat') ?>
			<?php endif?>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
