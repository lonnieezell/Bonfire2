<div class="row">
    <?php foreach ($stats  as $elem) : ?>
        <?php foreach ($elem->items() as $widget) : ?>
        <div class="col-3">
            <div class="widget-stats <?= $widget->bgColor() ?>">
                <div class="widget-stats-icon"><i class="<?= $widget->faIcon() ?>"></i></div>
                <div class="widget-stats-info">
                    <h4><?= $widget->title() ?></h4>
                    <p><?= $widget->value() ?></p>
                </div>
                <div class="widget-stats-link">
                    <a href="<?= $widget->url() ?>">View Detail <i class="fa fa-arrow-alt-circle-right"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach;?>
    <?php endforeach; ?>
</div>
