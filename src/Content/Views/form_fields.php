<?php
/**
 * The rows of a blueprint form: a label, the field's input, its instructions and its error.
 *
 * @var list<array{field: Bonfire\Content\Field, view: string, value: string, error: string|null, disabled: bool}> $rows
 */
?>
<?php foreach ($rows as $row) : ?>
    <?php $field = $row['field'] ?>
    <div class="form-group mb-3" data-field="<?= esc($field->handle, 'attr') ?>">
        <label for="field-<?= esc($field->handle, 'attr') ?>" class="form-label"><?= esc($field->label) ?></label>
        <?= view($row['view'], [
            'field'    => $field,
            'name'     => 'fields[' . $field->handle . ']',
            'id'       => 'field-' . $field->handle,
            'value'    => $row['value'],
            'disabled' => $row['disabled'],
        ], ['saveData' => false]) ?>
        <?php if ($field->instructions !== null) : ?>
            <div class="form-text"><?= esc($field->instructions) ?></div>
        <?php endif ?>
        <?php if ($row['error'] !== null) : ?>
            <p class="text-danger" data-error="<?= esc($field->handle, 'attr') ?>"><?= esc($row['error']) ?></p>
        <?php endif ?>
    </div>
<?php endforeach ?>
