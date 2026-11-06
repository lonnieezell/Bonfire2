<?php
/**
 * The sections of a form, each with its title and instructions and the rows of its fields in one to three columns:
 * a label, the field's input, its instructions and its error. Columns stack below `md`, and a full-width row spans
 * its section. Pass `rows` instead for a form with no sections.
 *
 * @var list<array{title: string|null, instructions: string|null, columns: int, rows: list<array{field: Bonfire\Fields\Field, view: string, value: string, error: string|null, disabled: bool, full: bool}>}> $sections
 * @var list<array{field: Bonfire\Fields\Field, view: string, value: string, error: string|null, disabled: bool, full: bool}>                                                                              $rows     used when `sections` isn't given
 */
$sections ??= [['title' => null, 'instructions' => null, 'columns' => 1, 'rows' => $rows]];
?>
<?php foreach ($sections as $section) : ?>
    <?php $cell = 'col-12' . ($section['columns'] === 1 ? '' : ' col-md-' . intdiv(12, $section['columns'])) ?>
    <?php if ($section['title'] !== null) : ?>
        <fieldset class="mb-4" data-section data-columns="<?= $section['columns'] ?>">
            <legend class="h5"><?= esc($section['title']) ?></legend>
            <?php if ($section['instructions'] !== null) : ?>
                <p class="text-muted"><?= esc($section['instructions']) ?></p>
            <?php endif ?>
    <?php endif ?>
    <div class="row">
        <?php foreach ($section['rows'] as $row) : ?>
            <?php $field = $row['field'] ?>
            <div class="<?= $row['full'] ? 'col-12' : $cell ?>">
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
            </div>
        <?php endforeach ?>
    </div>
    <?php if ($section['title'] !== null) : ?>
        </fieldset>
    <?php endif ?>
<?php endforeach ?>
