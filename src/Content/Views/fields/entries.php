<?php
/**
 * The input of an entries field: the entries of the allowed collections to pick from. One pick (`max_items: 1`)
 * is a select, and more are checkboxes. A link to an entry in the trash shows, muted, until it's unticked. Copy it to app/Views/content/fields/entries.php to change it.
 *
 * @var Bonfire\Content\Field $field
 * @var string                $name
 * @var string                $id
 * @var string                $value     the ids of the linked entries, as a JSON list
 * @var bool                  $disabled
 */
$offered  = Bonfire\Content\Fields\EntriesField::choices($field);
$chosen   = Bonfire\Content\Fields\ChoiceList::decode($value);
$max      = Bonfire\Content\Fields\EntriesField::maxItems($field);
$trashed  = Bonfire\Content\Fields\EntriesField::trashedLinks($field, $chosen);
$grouped  = count(array_unique(array_map(static fn (Bonfire\Content\Entry $entry): string => $entry->collection, [...$offered, ...$trashed]))) > 1;
$labelOf  = static fn (Bonfire\Content\Entry $entry): string => $grouped ? "{$entry->title} ({$entry->collection})" : $entry->title;
?>
<?php if ($offered === [] && $trashed === []) : ?>
    <p class="text-muted mb-0" id="<?= esc($id, 'attr') ?>"><?= lang('Content.entriesNone') ?></p>
<?php elseif ($max === 1) : ?>
    <select name="<?= esc($name . '[]', 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-select"<?= $disabled ? ' disabled' : '' ?>>
        <option value=""></option>
        <?php foreach ($offered as $entry) : ?>
            <option value="<?= esc($entry->id, 'attr') ?>"<?= in_array($entry->id, $chosen, true) ? ' selected' : '' ?>><?= esc($labelOf($entry)) ?></option>
        <?php endforeach ?>
        <?php foreach ($trashed as $entry) : ?>
            <option value="<?= esc($entry->id, 'attr') ?>" class="text-muted" data-trashed-link selected><?= esc($labelOf($entry)) ?> (<?= lang('Content.inTrash') ?>)</option>
        <?php endforeach ?>
    </select>
<?php else : ?>
    <div id="<?= esc($id, 'attr') ?>" class="border rounded p-2" style="max-height: 14rem; overflow-y: auto;">
        <?php foreach ($offered as $entry) : ?>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="<?= esc($name . '[]', 'attr') ?>" id="<?= esc($id . '-' . $entry->id, 'attr') ?>" value="<?= esc($entry->id, 'attr') ?>"<?= in_array($entry->id, $chosen, true) ? ' checked' : '' ?><?= $disabled ? ' disabled' : '' ?>>
                <label class="form-check-label" for="<?= esc($id . '-' . $entry->id, 'attr') ?>"><?= esc($labelOf($entry)) ?></label>
            </div>
        <?php endforeach ?>
        <?php foreach ($trashed as $entry) : ?>
            <div class="form-check text-muted" data-trashed-link="<?= esc($entry->id, 'attr') ?>">
                <input type="checkbox" class="form-check-input" name="<?= esc($name . '[]', 'attr') ?>" id="<?= esc($id . '-' . $entry->id, 'attr') ?>" value="<?= esc($entry->id, 'attr') ?>" checked<?= $disabled ? ' disabled' : '' ?>>
                <label class="form-check-label" for="<?= esc($id . '-' . $entry->id, 'attr') ?>"><?= esc($labelOf($entry)) ?></label>
                <x-status tone="muted" label="<?= esc(lang('Content.inTrash'), 'attr') ?>" />
            </div>
        <?php endforeach ?>
    </div>
    <?php if ($max !== null) : ?>
        <div class="form-text"><?= lang('Content.entriesLimit', [$max]) ?></div>
    <?php endif ?>
<?php endif ?>
