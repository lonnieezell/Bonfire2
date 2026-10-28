<?php
/**
 * Stands in for a widget added in edit mode until the layout is saved.
 *
 * @var Bonfire\Dashboard\DashboardWidget $widget
 */
?>
<section class="bf-card bf-widget-placeholder">
    <i class="<?= esc($widget->icon(), 'attr') ?>" aria-hidden="true"></i>
    <h3><?= esc($widget->title()) ?></h3>
    <p><?= esc($widget->description()) ?></p>
    <p class="bf-widget-placeholder-note"><?= lang('Bonfire.addedOnSave') ?></p>
</section>
