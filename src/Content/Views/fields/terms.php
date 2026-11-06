<?php
/**
 * The input of a terms field: a checkbox for each term of the taxonomy. Copy it to app/Views/fields/terms.php to change it.
 * A slug the entry has that no term has stays checked, so that saving doesn't drop it.
 *
 * @var Bonfire\Fields\Field $field
 * @var string               $name     the input's name, such as fields[tags]. The boxes add [].
 * @var string               $id       the id the boxes' ids start with
 * @var string               $value    the slugs the entry has, separated by commas
 * @var bool                 $disabled
 */

// Straight from the index, because the entry counts that content()->terms() adds aren't needed here.
$rows = (new Bonfire\Content\Models\ContentTermModel())->inTaxonomy(Bonfire\Content\Fields\TermsField::taxonomy($field), 'slug, title');

$terms = array_column($rows, 'title', 'slug');

$chosen = array_filter(explode(',', $value));

foreach ($chosen as $slug) {
    $terms[$slug] ??= lang('Content.unknownTerm', [$slug]);
}
?>
<div id="<?= esc($id, 'attr') ?>" data-terms>
    <?php if ($terms === []) : ?>
        <p class="text-muted mb-0"><?= lang('Content.noTerms') ?></p>
    <?php endif ?>
    <?php $n = 0 ?>
    <?php foreach ($terms as $slug => $title) : ?>
        <?php $n++ ?>
        <div class="form-check form-check-inline">
            <input type="checkbox" class="form-check-input" name="<?= esc($name, 'attr') ?>[]" id="<?= esc($id, 'attr') ?>-<?= $n ?>" value="<?= esc((string) $slug, 'attr') ?>"
                <?= in_array((string) $slug, $chosen, true) ? 'checked' : '' ?><?= $disabled ? ' disabled' : '' ?>>
            <label class="form-check-label" for="<?= esc($id, 'attr') ?>-<?= $n ?>"><?= esc($title) ?></label>
        </div>
    <?php endforeach ?>
</div>
