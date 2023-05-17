<div class="dashboard-cell-container row">
	<?php foreach ($cards  as $elem) : ?>

		<?php foreach ($elem->items() as $index => $widget) : ?>
			<?php
            $_widgets = array_values(
                array_filter($manager, static fn ($k) => $k['widget'] === 'Cards', ARRAY_FILTER_USE_BOTH)
            );
		    ?>

			<?php //if(true): 
            if (setting('Stats.' . $_widgets[$index]['widget'] . '_' . $_widgets[$index]['index'])) : 
            ?>
                <div class="col-6">
                    <div class="widget-stats <?= $widget->bgColor() ?>">
                        
                        <div class="widget-stats-info">
                            <h4><i class="<?= $widget->faIcon() ?>"></i> <?= $widget->title() ?></h4>
                            <div><?= $widget->value() ?></div>
                        </div>
						<?php if (setting('Cards.cards_showLink')) : ?>
                            <div class="widget-stats-link">
                                <a href="<?= site_url($widget->url()) ?>"><?= lang('Widgets.viewDetail') ?> <i class="fa fa-arrow-alt-circle-right"></i></a>
                            </div>
						<?php endif?>
                    </div>
                </div>
			<?php endif?>

		<?php endforeach; ?>

	<?php endforeach; ?>
</div>
