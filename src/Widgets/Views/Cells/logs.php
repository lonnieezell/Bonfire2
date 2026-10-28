<?php foreach ($logs as $widget) : ?>
    <?php if ($widget->settings()->enabled()) : ?>
        <?= $widget->render() ?>
    <?php endif ?>
<?php endforeach ?>
