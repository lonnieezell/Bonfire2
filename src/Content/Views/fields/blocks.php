<?php
/**
 * The input of a blocks field. Blocks are written in the entry's file, so this lists them and posts their values back,
 * which keeps them when the entry is saved. Copy it to app/Views/content/fields/blocks.php to change it.
 *
 * @var string                $name     the input's name, such as fields[sections]. Each value adds [position][handle].
 * @var string                $id
 * @var string                $value    the blocks, as a JSON list of maps
 * @var bool                  $disabled
 */
$blocks = Bonfire\Content\Fields\BlocksField::decode($value);
?>
<div id="<?= esc($id, 'attr') ?>" data-blocks>
    <?php if ($blocks === []) : ?>
        <p class="text-muted mb-0"><?= lang('Content.blocksNone') ?></p>
    <?php endif ?>
    <?php foreach ($blocks as $position => $block) : ?>
        <div data-block="<?= esc($block['type'], 'attr') ?>">
            <code><?= esc($block['type']) ?></code>
            <?php foreach ($block as $key => $text) : ?>
                <input type="hidden" name="<?= esc($name . '[' . $position . '][' . $key . ']', 'attr') ?>" value="<?= esc($text, 'attr') ?>"<?= $disabled ? ' disabled' : '' ?>>
            <?php endforeach ?>
        </div>
    <?php endforeach ?>
    <div class="form-text"><?= lang('Content.blocksHint') ?></div>
</div>
