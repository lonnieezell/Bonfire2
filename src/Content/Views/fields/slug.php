<?php
/**
 * The input of a slug field. While the entry is new, it's filled from the field named by `from` (the title
 * unless the blueprint says otherwise) until someone types in it. Copy it to app/Views/content/fields/slug.php
 * to change it.
 *
 * @var Bonfire\Content\Field $field
 * @var string                $name
 * @var string                $id
 * @var string                $value
 * @var bool                  $disabled   true once the entry exists, because the slug names its file
 * @var bool                  $isNew
 */
$sourceId = 'field-' . Bonfire\Content\Fields\SlugField::source($field);
?>
<div x-data="{ touched: <?= $value !== '' ? 'true' : 'false' ?> }"
    <?php if (! $disabled) : ?>
    x-init="const from = document.getElementById('<?= esc($sourceId, 'js') ?>'); from?.addEventListener('input', () => { if (! touched) { $refs.slug.value = from.value.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, ''); } })"
    <?php endif ?>>
    <input type="text" x-ref="slug" name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control" value="<?= esc($value, 'attr') ?>"<?= $disabled ? ' disabled' : ' @input="touched = true"' ?>>
</div>
<div class="form-text"><?= lang($disabled ? 'Content.slugLocked' : 'Content.slugHint') ?></div>
