<?php
/**
 * The input of a belongsTo field: a select of the records of its model. Copy it to app/Views/fields/belongsTo.php to change it.
 *
 * @var Bonfire\Fields\Field $field
 * @var string               $name
 * @var string               $id
 * @var string               $value     the ID of the chosen record
 * @var bool                 $disabled
 */
?>
<select name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-select"<?= $disabled ? ' disabled' : '' ?>>
    <option value=""></option>
    <?php foreach (Bonfire\Fields\Types\BelongsToField::choices($field) as $recordId => $title) : ?>
        <option value="<?= esc((string) $recordId, 'attr') ?>"<?= (string) $recordId === $value ? ' selected' : '' ?>><?= esc($title) ?></option>
    <?php endforeach ?>
</select>
