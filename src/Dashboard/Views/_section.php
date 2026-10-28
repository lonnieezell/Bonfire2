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
        <input type="text" class="bf-dashboard-section-input" data-section-title maxlength="100" @input="refresh()" @change="commit()"
            value="<?= esc($title, 'attr') ?>" placeholder="<?= esc(lang('Bonfire.sectionTitle'), 'attr') ?>" aria-label="<?= esc(lang('Bonfire.sectionTitle'), 'attr') ?>">
        <span class="bf-dashboard-section-count" x-text="(revision, widgetCount($el))"></span>
        <div class="dropdown">
            <button type="button" class="bf-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= esc(lang('Bonfire.sectionOptions'), 'attr') ?>">
                <i class="fas fa-ellipsis" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><button type="button" class="dropdown-item" @click="openLibrary($el)"><i class="fas fa-plus fa-fw" aria-hidden="true"></i> <?= lang('Bonfire.addWidgetHere') ?></button></li>
                <li><button type="button" class="dropdown-item" @click="moveSection($el, -1)" :disabled="(revision, ! canMoveSection($el, -1))"><i class="fas fa-arrow-up fa-fw" aria-hidden="true"></i> <?= lang('Bonfire.moveUp') ?></button></li>
                <li><button type="button" class="dropdown-item" @click="moveSection($el, 1)" :disabled="(revision, ! canMoveSection($el, 1))"><i class="fas fa-arrow-down fa-fw" aria-hidden="true"></i> <?= lang('Bonfire.moveDown') ?></button></li>
                <li><button type="button" class="dropdown-item" @click="addSection($el)" :disabled="sectionNames.length >= <?= Bonfire\Dashboard\LayoutValidator::MAX_SECTIONS ?>"><i class="fas fa-plus fa-fw" aria-hidden="true"></i> <?= lang('Bonfire.insertSection') ?></button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item text-danger" @click="removeSection($el)"><i class="fas fa-trash fa-fw" aria-hidden="true"></i> <?= lang('Bonfire.deleteSection') ?></button></li>
            </ul>
        </div>
    </div>
    <div class="bf-dashboard-canvas">
        <div class="bf-dashboard-guides" aria-hidden="true" x-show="editing" x-cloak><?= str_repeat('<span></span>', 12) ?></div>
        <div class="bf-dashboard-grid" data-section data-empty-label="<?= esc(lang('Bonfire.dropHere'), 'attr') ?>"><?= $tiles ?></div>
    </div>
    <button type="button" class="bf-dashboard-add-bar" x-show="editing" x-cloak @click="openLibrary($el)">
        <i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Bonfire.addWidget') ?>
    </button>
</section>
