<?php
/**
 * The records a belongsTo typeahead found, as the choices under its input. Choosing one runs the typeahead's `pick()`.
 *
 * @var array<string, string> $records title by ID
 */
?>
<?php if ($records === []) : ?>
    <div class="list-group-item text-muted"><?= lang('Fields.belongsToNoMatch') ?></div>
<?php endif ?>
<?php foreach ($records as $recordId => $title) : ?>
    <button type="button" role="option" class="list-group-item list-group-item-action" data-id="<?= esc((string) $recordId, 'attr') ?>" data-title="<?= esc($title, 'attr') ?>" @click="pick($el)"><?= esc($title) ?></button>
<?php endforeach ?>
