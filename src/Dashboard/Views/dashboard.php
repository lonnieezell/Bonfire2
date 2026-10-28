<?php $this->extend('master') ?>

<?php $this->section('styles') ?>

	<?= asset_link('admin/css/widgets.css', 'css'); ?>
	<?php // Before Alpine, so its alpine:init listener is in place when Alpine starts. ?>
	<?= asset_link('admin/js/dashboard.js', 'js') ?>

<?php $this->endSection() ?>


<?php $this->section('main') ?>

    <div class="bf-dashboard" x-data="dashboardEditor" :class="{ 'is-editing': editing, 'is-arranging': arranging, 'has-library': library }"
        @keydown.window="key($event)"
        data-save-url="<?= url_to('dashboard-layout') ?>"
        data-csrf-header="<?= esc(config('Security')->headerName, 'attr') ?>" data-csrf-hash="<?= csrf_hash() ?>"
        data-default-layout="<?= esc(json_encode($defaultLayout->toArray()), 'attr') ?>"
        data-size-columns="<?= esc(json_encode(array_combine(
            array_map(static fn ($size) => $size->value, Bonfire\Dashboard\Size::cases()),
            array_map(static fn ($size) => $size->columns(), Bonfire\Dashboard\Size::cases()),
        )), 'attr') ?>"
        data-messages="<?= esc(json_encode([
            'untitled'       => lang('Bonfire.untitledSection'),
            'widgetCount'    => lang('Bonfire.widgetCount'),
            'widgetCountOne' => lang('Bonfire.widgetCountOne'),
            'moved'          => lang('Bonfire.movedTo'),
            'added'          => lang('Bonfire.addedTo'),
            'removed'        => lang('Bonfire.removed'),
            'resized'        => lang('Bonfire.resized'),
            'sectionMoved'   => lang('Bonfire.sectionMoved'),
            'sectionAdded'   => lang('Bonfire.sectionAdded'),
            'sectionDeleted' => lang('Bonfire.sectionDeleted'),
            'reset'          => lang('Bonfire.layoutReset'),
            'undone'         => lang('Bonfire.undone'),
            'redone'         => lang('Bonfire.redone'),
        ]), 'attr') ?>">

        <div class="visually-hidden" aria-live="polite" x-text="announcement"></div>
        <p class="visually-hidden" id="bf-dashboard-help"><?= lang('Bonfire.customizeHelp') ?></p>

        <div class="bf-dashboard-toolbar" x-show="! editing">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="start()">
                <i class="fas fa-table-cells-large" aria-hidden="true"></i> <?= lang('Bonfire.customize') ?>
            </button>
        </div>

        <div class="bf-dashboard-editbar" x-show="editing" x-cloak>
            <span class="bf-dashboard-editbar-icon" aria-hidden="true"><i class="fas fa-table-cells-large"></i></span>
            <div class="bf-dashboard-editbar-text">
                <strong><?= lang('Bonfire.customizing') ?></strong>
                <small x-show="! failed" title="<?= esc(lang('Bonfire.customizeHelp'), 'attr') ?>"><?= lang('Bonfire.customizeHint') ?></small>
                <small class="text-danger" x-show="failed" role="alert"><?= lang('Bonfire.layoutFailed') ?></small>
            </div>
            <div class="bf-dashboard-editbar-actions">
                <button type="button" class="bf-icon-btn" @click="undo()" :disabled="! past.length" title="<?= esc(lang('Bonfire.undo'), 'attr') ?> (Ctrl+Z)" aria-label="<?= esc(lang('Bonfire.undo'), 'attr') ?>">
                    <i class="fas fa-rotate-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="bf-icon-btn" @click="redo()" :disabled="! future.length" title="<?= esc(lang('Bonfire.redo'), 'attr') ?> (Shift+Ctrl+Z)" aria-label="<?= esc(lang('Bonfire.redo'), 'attr') ?>">
                    <i class="fas fa-rotate-right" aria-hidden="true"></i>
                </button>
                <span class="bf-dashboard-editbar-divider" aria-hidden="true"></span>
                <button type="button" class="bf-icon-btn" @click="reset()" title="<?= esc(lang('Bonfire.resetLayout'), 'attr') ?>" aria-label="<?= esc(lang('Bonfire.resetLayout'), 'attr') ?>">
                    <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="openLibrary()">
                    <i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Bonfire.addWidget') ?>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="cancel()"><?= lang('Actions.cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" @click="save()" :disabled="saving">
                    <span x-show="dirty()"><?= lang('Bonfire.saveLayout') ?></span>
                    <span x-show="! dirty()"><?= lang('Bonfire.done') ?></span>
                </button>
            </div>
        </div>

        <div class="bf-dashboard-sections" data-sections>
			<?php foreach ($layout->sections as $section) : ?>
				<?php $tiles = '' ?>
				<?php foreach ($section->widgets as $placement) : ?>
					<?php $widget = $widgets[$placement->id] ?>
					<?php $tiles .= $this->setData(['widget' => $widget, 'placement' => $placement, 'html' => $widget->render()])->include('Bonfire\Dashboard\Views\_tile') ?>
				<?php endforeach ?>
				<?= $this->setData(['title' => $section->title, 'tiles' => $tiles])->include('Bonfire\Dashboard\Views\_section') ?>
			<?php endforeach ?>
        </div>

        <template x-ref="newSection">
			<?= $this->setData(['title' => '', 'tiles' => ''])->include('Bonfire\Dashboard\Views\_section') ?>
        </template>

        <div class="bf-dashboard-add-section" x-show="editing" x-cloak>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="addSection()" :disabled="sectionNames.length >= <?= Bonfire\Dashboard\LayoutValidator::MAX_SECTIONS ?>">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Bonfire.addSection') ?>
            </button>
        </div>

		<?= $this->setData(['groups' => $palette, 'placed' => $layout->ids()])->include('Bonfire\Dashboard\Views\_widget_palette') ?>

        <div class="bf-dashboard-toast" x-show="toast" x-cloak x-transition.opacity>
            <span x-text="toast"></span>
            <button type="button" x-show="toastUndo" @click="undo()"><?= lang('Bonfire.undo') ?></button>
        </div>
    </div>

<?php $this->endSection() ?>
