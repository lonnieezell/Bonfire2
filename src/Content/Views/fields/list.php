<?php
/**
 * The input of a list field: a card for each row, which can be opened, moved, copied and deleted, and a button that adds
 * a row. The new row's inputs come from the server, so they render through the same partials as the rest of the form.
 * Copy it to app/Views/fields/list.php to change it. Each row is drawn by block_card.php, and blocks_script.php moves them.
 *
 * @var Bonfire\Fields\Field  $field
 * @var string                $name     the input's name, such as fields[features]. A row's inputs add [key][handle].
 * @var string                $id
 * @var string                $value    the rows, as a JSON list of maps
 * @var bool                  $disabled
 * @var array<string, string> $errors   the errors inside the rows, by position and handle: `2.title`
 */
use Bonfire\Content\Fields\BlocksField;
use Bonfire\Content\Fields\ListField;

$errors ??= [];

$rows      = BlocksField::decode($value);
$blueprint = ListField::rowBlueprint($field);
$max       = BlocksField::bound($field, 'max_items');
$full      = $max !== null && count($rows) >= $max;
$list      = $id . '-list';
$token     = ListField::token($field);
$errorsOf  = [];

foreach ($errors as $where => $message) {
    [$position, $handle] = array_pad(explode('.', (string) $where, 2), 2, '');

    $errorsOf[(int) $position][$handle] = $message;
}
?>
<div id="<?= esc($id, 'attr') ?>" data-list x-data="bfBlocks(<?= (int) $max ?>, false)">
    <div id="<?= esc($list, 'attr') ?>" data-list-rows x-ref="list">
        <?php foreach ($rows as $index => $row) : ?>
            <?= view('Bonfire\Content\Views\block_card', [
                'name'       => $name,
                'id'         => $id,
                'key'        => BlocksField::newKey(),
                'block'      => $row,
                'blueprint'  => $blueprint,
                'cardErrors' => $errorsOf[$index + 1] ?? [],
                'position'   => $index + 1,
                'open'       => false,
                'slot'       => false,
                'disabled'   => $disabled,
                'isRow'      => true,
            ], ['saveData' => false]) ?>
        <?php endforeach ?>
    </div>
    <p class="text-muted" data-list-empty x-show="count === 0"<?= $rows === [] ? '' : ' x-cloak' ?>><?= lang('Content.listNone') ?></p>
    <?php if (! $disabled && $token === '') : ?>
        <p class="text-muted" data-list-no-key><?= lang('Content.listNoKey') ?></p>
    <?php elseif (! $disabled) : ?>
        <button type="button" class="btn btn-outline-primary" data-add-row :disabled="full"<?= $full ? ' disabled' : '' ?>
            hx-get="<?= url_to('content-list-row') ?>"
            hx-target="#<?= esc($list, 'attr') ?>"
            hx-swap="beforeend"
            hx-vals="js:{token: '<?= esc($token, 'js') ?>', name: '<?= esc($name, 'js') ?>', id: '<?= esc($id, 'js') ?>', count: document.getElementById('<?= esc($list, 'js') ?>').children.length}"
        ><?= lang('Content.listAdd') ?></button>
    <?php endif ?>
</div>
<?= view('Bonfire\Content\Views\blocks_script', [], ['saveData' => false]) ?>
