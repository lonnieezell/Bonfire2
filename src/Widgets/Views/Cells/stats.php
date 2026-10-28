<?php

use Bonfire\Dashboard\WidgetValues;
use Bonfire\Dashboard\Widgets\StatsWidget;

?>
<div class="bf-stats-grid">
	<?php foreach ($stats  as $elem) : ?>

		<?php foreach ($elem->items() as $widget) : ?>

			<?php if ($widget->settings()->enabled()) : ?>
				<?= $this->setData(['stat' => $widget, 'showLink' => WidgetValues::resolve((new StatsWidget($widget))->fields())->get('showLink')])->include('Bonfire\Widgets\Views\Cells\_stat') ?>
			<?php endif?>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
