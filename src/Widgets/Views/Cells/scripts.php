<?php foreach ($charts as $elem) : ?>
    <?php foreach ($elem->items() as $widget) : ?>

        <?php if ($widget->settings()->enabled()) : ?>
            <?= $widget->getScript(); ?>
        <?php endif?>

    <?php endforeach; ?>
<?php endforeach; ?>
