<?php $this->extend('master') ?>

<?php $this->section('styles') ?>

	<?= asset_link('admin/css/widgets.css', 'css'); ?>
	<?php // Before Alpine, so its alpine:init listener is in place when Alpine starts. ?>
	<?= asset_link('admin/js/dashboard.js', 'js') ?>

<?php $this->endSection() ?>


<?php $this->section('main') ?>

    <div class="bf-dashboard" x-data="dashboardEditor" :class="{ 'is-editing': editing }"
        data-save-url="<?= url_to('dashboard-layout') ?>"
        data-csrf-header="<?= esc(config('Security')->headerName, 'attr') ?>" data-csrf-hash="<?= csrf_hash() ?>">

        <div class="bf-dashboard-toolbar">
            <button type="button" class="btn btn-outline-secondary btn-sm" x-show="! editing" @click="start()">
                <i class="fas fa-pen" aria-hidden="true"></i> <?= lang('Bonfire.customize') ?>
            </button>
        </div>

        <div class="bf-dashboard-sections" data-sections>
			<?php foreach ($layout->sections as $section) : ?>
				<?php $tiles = '' ?>
				<?php foreach ($section->widgets as $placement) : ?>
					<?php $widget = $dashboard->widget($placement->id) ?>
					<?php $html = $widget->render() ?>
					<?php if ($html !== '') {
					    $tiles .= $this->setData(['widget' => $widget, 'placement' => $placement, 'html' => $html])->include('Bonfire\Dashboard\Views\_tile');
					} ?>
				<?php endforeach ?>
				<?= $this->setData(['title' => $section->title, 'tiles' => $tiles])->include('Bonfire\Dashboard\Views\_section') ?>
			<?php endforeach ?>
        </div>

        <template x-ref="newSection">
			<?= $this->setData(['title' => '', 'tiles' => ''])->include('Bonfire\Dashboard\Views\_section') ?>
        </template>

        <div class="bf-dashboard-add-section" x-show="editing" x-cloak>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="addSection()" :disabled="sectionCount >= <?= Bonfire\Dashboard\LayoutValidator::MAX_SECTIONS ?>">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Bonfire.addSection') ?>
            </button>
        </div>

		<?= $this->setData(['groups' => $palette, 'placed' => $layout->ids()])->include('Bonfire\Dashboard\Views\_widget_palette') ?>

        <div class="bf-floating-bar" x-show="editing" x-cloak>
            <div class="bf-floating-bar-inner bf-dashboard-editbar">
                <span class="bf-dashboard-editbar-text" x-show="! failed"><?= lang('Bonfire.customizing') ?></span>
                <span class="bf-dashboard-editbar-text text-danger" x-show="failed" role="alert"><?= lang('Bonfire.layoutFailed') ?></span>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#bf-widget-palette" aria-controls="bf-widget-palette">
                    <i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Bonfire.addWidgets') ?>
                </button>
                <form method="post" action="<?= url_to('dashboard-layout-reset') ?>">
					<?= csrf_field() ?>
                    <button type="submit" class="btn btn-link" data-confirm="<?= esc(lang('Bonfire.resetLayoutQ'), 'attr') ?>" data-confirm-button="<?= esc(lang('Bonfire.resetLayout'), 'attr') ?>">
						<?= lang('Bonfire.resetLayout') ?>
                    </button>
                </form>
                <button type="button" class="btn btn-outline-secondary" @click="cancel()"><?= lang('Actions.cancel') ?></button>
                <button type="button" class="btn btn-primary" @click="save()" :disabled="saving"><?= lang('Bonfire.save') ?></button>
            </div>
        </div>
    </div>

<?php $this->endSection() ?>
