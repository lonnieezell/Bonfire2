<div class="bf-stats-grid">
	<?php foreach ($stats  as $elem) : ?>

		<?php foreach ($elem->items() as $widget) : ?>

			<?php if ($widget->settings()->enabled()) : ?>
                <div class="bf-stat">
                    <div class="bf-stat-label">
                        <i class="<?= $widget->faIcon() ?>" aria-hidden="true"></i>
                        <h3><?= $widget->title() ?></h3>
                    </div>
                    <p class="bf-stat-value"><?= $widget->value() ?></p>
					<?php if (setting('Stats.stats_showLink')) : ?>
                        <a class="bf-stat-link" href="<?= site_url($widget->url()) ?>"><?= lang('Widgets.viewDetail') ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
					<?php endif?>
                </div>
			<?php endif?>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
