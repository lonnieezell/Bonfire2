<div class="dashboard-cell-container row">
	<?php foreach ($stats  as $elem) : ?>

		<?php foreach ($elem->items() as $index => $widget) : ?>
			<?php
            $_widgets = array_filter($manager, static fn ($k) => $k['widget'] === 'Stats', ARRAY_FILTER_USE_BOTH);
		    ?>

			<?php if (setting('Stats.' . $_widgets[$index]['widget'] . '_' . $_widgets[$index]['index'])) : ?>
                <div class="col-3">
                    <div class="widget-stats <?= $widget->bgColor() ?>">
                        <div class="widget-stats-icon"><i class="<?= $widget->faIcon() ?>"></i></div>
                        <div class="widget-stats-info">
                            <h4><?= $widget->title() ?></h4>
                            <p><?= $widget->value() ?></p>
                        </div>
						<?php if (setting('Stats.stats_showLink')) : ?>
                            <div class="widget-stats-link">
                                <a href="<?= $widget->url() ?>">View Detail <i class="fa fa-arrow-alt-circle-right"></i></a>
                            </div>
						<?php endif?>
                    </div>
                </div>
			<?php endif?>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
