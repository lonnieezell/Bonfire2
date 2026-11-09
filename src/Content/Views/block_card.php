<?php
/**
 * One block of a blocks field: a collapsible card with the block's type as its title, a summary, the buttons that move,
 * copy and delete it, and an input for each of its fields. The inputs render through the same partials as the rest of the
 * form, so an app's own copies and its own field types work in here. The blocks field's input renders the blocks it
 * has through this, and the admin sends it for a block that is added.
 *
 * @var string                    $name        the blocks field's input name, such as fields[sections]. The block's inputs add [key][handle].
 * @var string                    $id          the blocks field's input id. The block's inputs add -key-handle.
 * @var string                    $key         what names this block's inputs, such as b_3f9a2c. Its position in the form is the order they're posted in.
 * @var array<string, string>     $block       the form value of the block: its `type`, the text of each field and, for a type that is gone, `kept`
 * @var Bonfire\Content\Blueprint|null $blueprint   the blueprint of the block's type, or null when there is none any more
 * @var array<string, string>     $cardErrors  this block's errors: a field's handle and the message, or `''` for the block itself
 * @var int                       $position    where the block is in the list, counting from 1, which its errors name
 * @var bool                      $open        whether the card shows its fields
 * @var bool                      $slot        whether the field holds one block, which is shown without the list's buttons
 * @var bool                      $disabled
 * @var bool                      $isRow       whether this is a row of a list field, which has no type, so a sub-field can be named `type`
 */
$isRow ??= false;

$type         = $isRow ? '' : $block['type'];
$prefix       = $name . '[' . $key . ']';
$title        = $blueprint?->title ?? $type;
$summary      = '';
$summaryField = null;
$icon         = $blueprint?->icon;

// The first text field is what tells blocks of one type apart in a list of collapsed cards.
foreach ($blueprint?->fields ?? [] as $blockField) {
    if ($blockField->type === 'text') {
        $summaryField = $blockField->handle;
        $summary      = mb_strimwidth($block[$blockField->handle] ?? '', 0, 80, '…');
        break;
    }
}

$open  = $open || $slot || $cardErrors !== [];
$tools = ! $slot && ! $disabled;
?>
<div class="card mb-2<?= $cardErrors === [] ? '' : ' border-danger' ?>" data-block="<?= esc($type, 'attr') ?>"<?= $isRow ? ' data-row' : '' ?> data-block-key="<?= esc($key, 'attr') ?>"<?= $summaryField !== null ? ' data-summary-field="' . esc($summaryField, 'attr') . '"' : '' ?>
    x-data="{ open: <?= $open ? 'true' : 'false' ?> }" @input="summarize($event)">
    <div class="card-header d-flex align-items-center gap-2 py-2">
        <?php if ($tools) : ?>
            <span class="text-muted" data-handle style="cursor: grab" title="<?= esc(lang('Content.blockDrag'), 'attr') ?>"><i class="fas fa-grip-vertical" aria-hidden="true"></i></span>
        <?php endif ?>
        <?php if ($icon !== null && preg_match('/^[a-z0-9-]+$/', $icon) === 1) : ?>
            <i class="fas fa-<?= $icon ?> text-muted" aria-hidden="true"></i>
        <?php endif ?>
        <?php if ($slot) : ?>
            <strong class="flex-grow-1 text-truncate" data-block-title><?= esc($title) ?></strong>
        <?php else : ?>
            <button type="button" class="btn btn-link text-decoration-none text-start text-truncate p-0 flex-grow-1" data-block-toggle
                @click="open = ! open" aria-expanded="<?= $open ? 'true' : 'false' ?>" :aria-expanded="open" title="<?= esc(lang('Content.blockToggle'), 'attr') ?>">
                <strong data-block-title><?= esc($title) ?></strong>
                <span class="text-muted ms-2" data-summary><?= esc($summary) ?></span>
            </button>
        <?php endif ?>
        <?php if ($tools) : ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-block-up @click="move($el, -1)" title="<?= esc(lang('Content.blockMoveUp'), 'attr') ?>" aria-label="<?= esc(lang('Content.blockMoveUp'), 'attr') ?>"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-block-down @click="move($el, 1)" title="<?= esc(lang('Content.blockMoveDown'), 'attr') ?>" aria-label="<?= esc(lang('Content.blockMoveDown'), 'attr') ?>"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-block-duplicate :disabled="full" @click="duplicate($el)" title="<?= esc(lang('Content.blockDuplicate'), 'attr') ?>" aria-label="<?= esc(lang('Content.blockDuplicate'), 'attr') ?>"><i class="fas fa-copy" aria-hidden="true"></i></button>
        <?php endif ?>
        <?php if (! $disabled) : ?>
            <button type="button" class="btn btn-sm btn-outline-danger" data-block-delete @click="remove($el)" title="<?= esc(lang('Content.blockDelete'), 'attr') ?>" aria-label="<?= esc(lang('Content.blockDelete'), 'attr') ?>"><i class="fas fa-trash" aria-hidden="true"></i></button>
        <?php endif ?>
    </div>
    <div class="card-body" data-block-body x-show="open"<?= $open ? '' : ' x-cloak' ?>>
        <?php // Always posted, so a card whose inputs post nothing when empty is still saved. A handle can't start with `_`, so `_row` is never a sub-field's. ?>
        <input type="hidden" name="<?= esc($prefix . ($isRow ? '[_row]' : '[type]'), 'attr') ?>" value="<?= esc($type, 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
        <?php if ($cardErrors !== []) : ?>
            <div class="alert alert-danger" role="alert" data-block-errors>
                <?php foreach ($cardErrors as $handle => $message) : ?>
                    <p class="mb-0" data-error="<?= esc($handle === '' ? (string) $position : $position . '.' . $handle, 'attr') ?>"><?= esc($isRow ? lang('Content.listError', [$position, $message]) : lang('Content.blockError', [$position, $title, $message])) ?></p>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <?php if ($blueprint === null) : ?>
            <p class="text-muted mb-0" data-block-unknown><?= esc(lang('Content.blockUnknown', [$type])) ?></p>
            <?php foreach (['kept', 'signed'] as $carried) : ?>
                <?php if (isset($block[$carried])) : ?>
                    <input type="hidden" name="<?= esc($prefix . '[' . $carried . ']', 'attr') ?>" value="<?= esc($block[$carried], 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
                <?php endif ?>
            <?php endforeach ?>
        <?php else : ?>
            <?php $form = new Bonfire\Fields\Form($blueprint->fields, $disabled ? array_map(static fn (Bonfire\Fields\Field $field): string => $field->handle, $blueprint->fields) : [], $blueprint->sections) ?>
            <?= view('Bonfire\Fields\Views\form_fields', [
                'sections'   => $form->sections($block, []),
                'namePrefix' => $prefix,
                'idPrefix'   => $id . '-' . $key,
            ], ['saveData' => false]) ?>
        <?php endif ?>
    </div>
</div>
