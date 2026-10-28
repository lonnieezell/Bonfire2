<?php
/**
 * One dashboard section: its heading when titled, its edit-mode controls
 * and the grid of its tiles. Also the template new sections are made from.
 *
 * @var string $title
 * @var string $tiles Rendered tiles
 */
?>
<section class="bf-dashboard-section" data-dashboard-section>
	<?php if ($title !== '') : ?>
        <h2 class="bf-dashboard-section-title" x-show="! editing"><?= esc($title) ?></h2>
	<?php endif ?>
    <div class="bf-dashboard-section-head" x-show="editing" x-cloak>
        <button type="button" class="bf-section-handle" aria-label="<?= esc(lang('Bonfire.dragSection'), 'attr') ?>">
            <i class="fas fa-grip-vertical" aria-hidden="true"></i>
        </button>
        <input type="text" class="form-control form-control-sm" data-section-title maxlength="100"
            value="<?= esc($title, 'attr') ?>" placeholder="<?= esc(lang('Bonfire.sectionTitle'), 'attr') ?>" aria-label="<?= esc(lang('Bonfire.sectionTitle'), 'attr') ?>">
        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeSection($el)" :disabled="sectionCount < 2"
            aria-label="<?= esc(lang('Bonfire.deleteSection'), 'attr') ?>">
            <i class="fas fa-trash" aria-hidden="true"></i>
        </button>
    </div>
    <div class="bf-dashboard-grid" data-section><?= $tiles ?></div>
</section>
