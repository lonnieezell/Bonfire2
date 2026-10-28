<?php
/**
 * A placed widget: its edit-mode tools and resize handle, and its content.
 * In edit mode the tile itself takes focus, for the keyboard controls.
 *
 * @var Bonfire\Dashboard\DashboardWidget $widget
 * @var Bonfire\Dashboard\Placement       $placement
 * @var string                            $html    The widget's rendered content
 */
$presets = $widget->sizes()->presets();
?>
<div class="bf-widget bf-span-<?= $placement->size->columns() ?>" data-widget-id="<?= esc($placement->id, 'attr') ?>" data-size="<?= $placement->size->value ?>" data-title="<?= esc($widget->title(), 'attr') ?>"
    data-sizes="<?= implode(' ', array_map(static fn ($size) => $size->value, $presets)) ?>"
    :tabindex="editing ? 0 : false" :role="editing && 'group'" :aria-label="editing && $el.dataset.title" :aria-describedby="editing && 'bf-dashboard-help'"
    @keydown="tileKey($event)" @click="editing && $el.focus()">
    <template x-if="editing">
        <div class="bf-widget-tools">
			<?php foreach ($presets as $size) : ?>
                <button type="button" class="bf-widget-size" data-size-option="<?= $size->value ?>" @click="resize($el)"
                    :aria-pressed="(revision, $el.closest('.bf-widget').dataset.size === '<?= $size->value ?>')"
                    title="<?= esc(lang('Bonfire.size' . $size->name), 'attr') ?>" aria-label="<?= esc(lang('Bonfire.size' . $size->name), 'attr') ?>"><?= lang('Bonfire.sizeLabels.' . $size->name) ?></button>
			<?php endforeach ?>
            <span class="bf-widget-tools-divider" aria-hidden="true"></span>
			<?php if (($settings = $widget->settingsUrl()) !== null) : ?>
                <a class="bf-widget-tool" href="<?= esc($settings, 'attr') ?>" data-widget-settings title="<?= esc(lang('Bonfire.widgetSettings'), 'attr') ?>" aria-label="<?= esc(lang('Bonfire.widgetSettings'), 'attr') ?>">
                    <i class="fas fa-sliders" aria-hidden="true"></i>
                </a>
			<?php endif ?>
            <button type="button" class="bf-widget-tool bf-widget-remove" @click="removeWidget($el)" title="<?= esc(lang('Bonfire.removeWidget', [$widget->title()]), 'attr') ?>" aria-label="<?= esc(lang('Bonfire.removeWidget', [$widget->title()]), 'attr') ?>">
                <i class="fas fa-trash" aria-hidden="true"></i>
            </button>
        </div>
    </template>
    <template x-if="editing">
        <span class="bf-widget-resize" @pointerdown="startResize($event)" aria-hidden="true"></span>
    </template>
    <div class="bf-widget-body" :inert="editing">
		<?= $html ?>
    </div>
</div>
