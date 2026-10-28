<?php

use Bonfire\Dashboard\DashboardWidget;

/**
 * The offcanvas where a placed widget's settings are changed in edit mode,
 * in the palette's place, and the form of every widget that has settings.
 * The editor fills a copy of the widget's form from its tile; Apply keeps
 * the values that differ from what each field inherits, on the tile.
 *
 * @var array<string, DashboardWidget> $widgets
 */
?>
<div class="offcanvas offcanvas-end bf-widget-palette" tabindex="-1" data-bs-backdrop="false" data-bs-scroll="true" id="bf-widget-settings" aria-labelledby="bf-widget-settings-title">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="bf-widget-settings-title">
            <i :class="settingsIcon" aria-hidden="true"></i> <span x-text="settingsTitle"></span>
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?= esc(lang('Actions.cancel'), 'attr') ?>"></button>
    </div>
    <form class="offcanvas-body bf-widget-settings" @submit.prevent="applySettings()">
        <div data-settings-fields></div>
        <div class="bf-widget-settings-actions">
            <button type="button" class="btn btn-link btn-sm" @click="resetSettings()"><?= lang('Bonfire.resetToDefaults') ?></button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="offcanvas"><?= lang('Actions.cancel') ?></button>
            <button type="submit" class="btn btn-primary btn-sm"><?= lang('Bonfire.applySettings') ?></button>
        </div>
    </form>

	<?php foreach ($widgets as $widget) : ?>
		<?php if (($fields = $widget->fields()) === []) {
		    continue;
		} ?>
        <template data-settings-for="<?= esc($widget->id(), 'attr') ?>" data-fields="<?= esc(json_encode(array_map(static fn ($field) => [
		    'name'      => $field->name,
		    'type'      => $field->type,
		    'inherited' => $field->inherited(),
		], $fields)), 'attr') ?>" data-title="<?= esc(lang('Bonfire.widgetSettingsFor', [$widget->title()]), 'attr') ?>" data-icon="<?= esc($widget->icon(), 'attr') ?>">
            <div>
				<?php foreach ($fields as $field) : ?>
					<?= view('Bonfire\Dashboard\Views\_widget_field', ['field' => $field, 'value' => null]) ?>
				<?php endforeach ?>
            </div>
        </template>
	<?php endforeach ?>
</div>
