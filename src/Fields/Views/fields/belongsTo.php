<?php
/**
 * The input of a belongsTo field: a select of the records of its model, or above `searchAbove` records a typeahead.
 * The typeahead is a text input that takes the record's ID, and with JavaScript a search box in its place that asks
 * the options endpoint for matching titles. Copy it to app/Views/fields/belongsTo.php to change it.
 *
 * @var Bonfire\Fields\Field $field
 * @var string               $name
 * @var string               $id
 * @var string               $value     the ID of the chosen record
 * @var bool                 $disabled
 */
?>
<?php if (Bonfire\Fields\Types\BelongsToField::searches($field)) : ?>
    <?php // The ID input is the one that's posted. It's visible only without JavaScript. The search box has `form` set to nothing, so it's never posted. ?>
    <div class="position-relative"
         x-data="{ pick(el) { this.$refs.id.value = el.dataset.id; this.$refs.search.value = el.dataset.title; this.$refs.options.replaceChildren(); } }"
         @click.outside="$refs.options.replaceChildren()">
        <input type="text" name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" value="<?= esc($value, 'attr') ?>" class="form-control"
               placeholder="<?= esc(lang('Fields.belongsToIdPlaceholder'), 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>
               x-ref="id" x-init="$el.type = 'hidden'">
        <input type="search" name="q" form="" value="<?= esc(Bonfire\Fields\Types\BelongsToField::titleOf($field, $value), 'attr') ?>" class="form-control" autocomplete="off" role="combobox" aria-controls="<?= esc($id, 'attr') ?>-options" aria-label="<?= esc($field->label, 'attr') ?>"
               hidden<?= $disabled ? ' disabled' : '' ?>
               x-ref="search" x-init="$el.hidden = false" @input="$refs.id.value = ''"
               hx-get="<?= esc(route_to('fields-options'), 'attr') ?>"
               hx-vals="<?= esc(json_encode(['field' => Bonfire\Fields\Types\BelongsToField::token($field)], JSON_THROW_ON_ERROR), 'attr') ?>"
               hx-trigger="input changed delay:300ms, focus"
               hx-target="#<?= esc($id, 'attr') ?>-options"
               hx-swap="innerHTML">
        <div id="<?= esc($id, 'attr') ?>-options" class="list-group position-absolute w-100 shadow" style="z-index: 1000" role="listbox" x-ref="options"></div>
    </div>
<?php else : ?>
    <select name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-select"<?= $disabled ? ' disabled' : '' ?>>
        <option value=""></option>
        <?php foreach (Bonfire\Fields\Types\BelongsToField::choices($field) as $recordId => $title) : ?>
            <option value="<?= esc((string) $recordId, 'attr') ?>"<?= (string) $recordId === $value ? ' selected' : '' ?>><?= esc($title) ?></option>
        <?php endforeach ?>
    </select>
<?php endif ?>
