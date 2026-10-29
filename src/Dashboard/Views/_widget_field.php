<?php

use Bonfire\Dashboard\WidgetField;

/**
 * The input of one widget field, in its setting row.
 *
 * @var WidgetField                $field
 * @var bool|float|int|string|null $value Its value, or null for one the dashboard editor fills in
 */
$id        = 'bf-widget-setting-' . $field->name;
$segmented = $field->type === 'choice' && count($field->options) <= 4;
?>
<x-setting-row label="<?= esc($field->label, 'attr') ?>"<?= $segmented ? '' : ' for="' . $id . '"' ?><?= $field->description === null ? '' : ' description="' . esc($field->description, 'attr') . '"' ?>>
	<?php if ($field->type === 'toggle') : ?>
        <x-switch name="<?= esc($field->name, 'attr') ?>" value="1" offValue="" id="<?= $id ?>" checked="<?= $value ? '1' : '0' ?>" />
	<?php elseif ($segmented) : ?>
        <x-segmented name="<?= esc($field->name, 'attr') ?>" label="<?= esc($field->label, 'attr') ?>" value="<?= esc((string) $value, 'attr') ?>" options="<?= esc(json_encode($field->options), 'attr') ?>" />
	<?php elseif ($field->type === 'choice') : ?>
        <select class="form-select" name="<?= esc($field->name, 'attr') ?>" id="<?= $id ?>">
			<?php foreach ($field->options as $option => $label) : ?>
                <option value="<?= esc((string) $option, 'attr') ?>"<?= $value !== null && (string) $option === (string) $value ? ' selected' : '' ?>><?= esc($label) ?></option>
			<?php endforeach ?>
        </select>
	<?php elseif ($field->type === 'number') : ?>
        <input type="number" class="form-control bf-input-narrow" name="<?= esc($field->name, 'attr') ?>" id="<?= $id ?>" step="<?= $field->step ?>"<?= $field->min === null ? '' : ' min="' . $field->min . '"' ?><?= $field->max === null ? '' : ' max="' . $field->max . '"' ?><?= $value === null ? '' : ' value="' . esc((string) $value, 'attr') . '"' ?> required>
	<?php else : ?>
        <input type="text" class="form-control" name="<?= esc($field->name, 'attr') ?>" id="<?= $id ?>" maxlength="<?= WidgetField::MAX_TEXT ?>"<?= $value === null ? '' : ' value="' . esc((string) $value, 'attr') . '"' ?>>
	<?php endif ?>
</x-setting-row>
