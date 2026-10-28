<?php foreach ($charts as $elem) : ?>
    <?php foreach ($elem->items() as $widget) : ?>

        <?= $widget->getScript(); ?>

    <?php endforeach; ?>
<?php endforeach; ?>
