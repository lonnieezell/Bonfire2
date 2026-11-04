<?php
/**
 * The input of a select field. Copy it to app/Views/content/fields/select.php to change it.
 *
 * @var Bonfire\Content\Field $field
 * @var string                $name
 * @var string                $id
 * @var string                $value     the chosen value, or with `multiple: true` a list of them as JSON
 * @var bool                  $disabled
 */
$multiple = Bonfire\Content\Fields\SelectField::isMultiple($field);
$chosen   = $multiple ? Bonfire\Content\Fields\ChoiceList::decode($value) : [$value];
?>
<select name="<?= esc($name . ($multiple ? '[]' : ''), 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-select"<?= $multiple ? ' multiple' : '' ?><?= $disabled ? ' disabled' : '' ?>>
    <?php if (! $multiple) : ?>
        <option value=""></option>
    <?php endif ?>
    <?php foreach (Bonfire\Content\Fields\SelectField::choices($field) as $optionValue => $label) : ?>
        <option value="<?= esc((string) $optionValue, 'attr') ?>"<?= in_array((string) $optionValue, $chosen, true) ? ' selected' : '' ?>><?= esc($label) ?></option>
    <?php endforeach ?>
</select>
