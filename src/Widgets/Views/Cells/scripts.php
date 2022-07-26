<?php foreach ($charts as $elem) : ?>
    <?php foreach ($elem->items() as $index => $widget) : ?>

        <?php
            $_widgets = array_values(
    array_filter($manager, static fn ($k) => $k['widget'] === 'Charts', ARRAY_FILTER_USE_BOTH)
);
        ?>
        <?php if (setting('Stats.' . $_widgets[$index]['widget'] . '_' . $index)) : ?>
            <?= $widget->getScript(); ?>
        <?php endif?>

    <?php endforeach; ?>
<?php endforeach; ?>
