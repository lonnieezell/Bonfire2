<?php foreach ($charts as $elem) : ?>
    <?php foreach ($elem->items() as $widget) : ?>
    <?php if ($widget->permission() !== null && ! auth()->user()?->can($widget->permission())) { continue; } ?>

        <?= $widget->getScript(); ?>

    <?php endforeach; ?>
<?php endforeach; ?>
