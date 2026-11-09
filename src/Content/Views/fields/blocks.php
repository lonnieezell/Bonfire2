<?php
/**
 * The input of a blocks field: a card for each block, which can be opened, moved, copied and deleted, and a menu that adds
 * a block of one of the types the field allows. The new block's inputs come from the server, so they render through the
 * same partials as the rest of the form. A field with max_items of 1 holds a single block, shown without the list's buttons.
 * Copy it to app/Views/fields/blocks.php to change it. Each block is drawn by block_card.php.
 *
 * @var Bonfire\Fields\Field  $field
 * @var string                $name     the input's name, such as fields[sections]. A block's inputs add [key][handle].
 * @var string                $id
 * @var string                $value    the blocks, as a JSON list of maps
 * @var bool                  $disabled
 * @var array<string, string> $errors   the errors inside the blocks, by position and handle: `2.heading`, or `2` for a whole block
 */
use Bonfire\Content\Fields\BlocksField;

$errors ??= [];

$blocks     = BlocksField::decode($value);
$types      = BlocksField::types($field);
$blueprints = BlocksField::blueprintsFor([...$types, ...array_column($blocks, 'type')]);
$max        = BlocksField::bound($field, 'max_items');
$slot       = $max === 1;
$full       = ! $slot && $max !== null && count($blocks) >= $max;
$list       = $id . '-list';
$errorsOf   = BlocksField::errorsByPosition($errors);
?>
<div id="<?= esc($id, 'attr') ?>" data-blocks<?= $slot ? ' data-slot' : '' ?> x-data="bfBlocks(<?= (int) $max ?>, <?= $slot ? 'true' : 'false' ?>)">
    <div id="<?= esc($list, 'attr') ?>" data-blocks-list x-ref="list">
        <?php foreach ($blocks as $index => $block) : ?>
            <?= view('Bonfire\Content\Views\block_card', [
                'name'       => $name,
                'id'         => $id,
                'key'        => BlocksField::newKey(),
                'block'      => $block,
                'blueprint'  => $blueprints[$block['type']] ?? null,
                'cardErrors' => $errorsOf[$index + 1] ?? [],
                'position'   => $index + 1,
                'open'       => false,
                'slot'       => $slot,
                'disabled'   => $disabled,
            ], ['saveData' => false]) ?>
        <?php endforeach ?>
    </div>
    <p class="text-muted" data-blocks-empty x-show="count === 0"<?= $blocks === [] ? '' : ' x-cloak' ?>><?= lang('Content.blocksNone') ?></p>
    <?php if (! $disabled) : ?>
        <div class="dropdown">
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-add-block
                :disabled="full"<?= $full ? ' disabled' : '' ?>>
                <?php if ($slot) : ?>
                    <span x-show="count === 0"<?= $blocks === [] ? '' : ' x-cloak' ?>><?= lang('Content.blockChoose') ?></span>
                    <span x-show="count > 0"<?= $blocks === [] ? ' x-cloak' : '' ?>><?= lang('Content.blockReplace') ?></span>
                <?php else : ?>
                    <?= lang('Content.blockAdd') ?>
                <?php endif ?>
            </button>
            <ul class="dropdown-menu">
                <?php foreach ($types as $type) : ?>
                    <li>
                        <button type="button" class="dropdown-item" data-add-type="<?= esc($type, 'attr') ?>"
                            hx-get="<?= url_to('content-block', $field->handle, $type) . ($slot ? '?slot=1' : '') ?>"
                            hx-target="#<?= esc($list, 'attr') ?>"
                            hx-swap="<?= $slot ? 'innerHTML' : 'beforeend' ?>"
                            <?php if (! $slot && $max !== null) : ?>
                                hx-vals="js:{count: document.getElementById('<?= esc($list, 'js') ?>').children.length, max: <?= $max ?>}"
                            <?php endif ?>
                        ><?= esc(($blueprints[$type] ?? null)?->title ?? $type) ?></button>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>
</div>
<?= view('Bonfire\Content\Views\blocks_script', [], ['saveData' => false]) ?>
