<div class="row">
    <?php foreach ($charts  as $elem) : ?>
        <?php foreach ($elem->items() as $widget) : ?>
            <div class="<?= $widget->cssClass() ?>">
                <canvas id="<?= $widget->chartName() ?>" class="chart-border"></canvas>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
