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

		<?php foreach ($layout->sections as $section) : ?>
            <section class="bf-dashboard-section">
				<?php if ($section->title !== '') : ?>
                    <h2 class="bf-dashboard-section-title"><?= esc($section->title) ?></h2>
				<?php endif ?>
                <div class="bf-dashboard-grid" data-section>
					<?php foreach ($section->widgets as $placement) : ?>
						<?php $widget = $dashboard->widget($placement->id) ?>
						<?php $html = $widget->render() ?>
						<?php if ($html === '') {
						    continue;
						} ?>
                        <div class="bf-widget bf-span-<?= $placement->size->columns() ?>" data-widget-id="<?= esc($placement->id, 'attr') ?>" data-size="<?= $placement->size->value ?>">
                            <button type="button" class="bf-widget-handle" x-show="editing" x-cloak aria-label="<?= esc(lang('Bonfire.dragWidget', [$widget->title()]), 'attr') ?>">
                                <i class="fas fa-grip-vertical" aria-hidden="true"></i>
                            </button>
                            <div class="bf-widget-body" :inert="editing">
								<?= $html ?>
                            </div>
                        </div>
					<?php endforeach ?>
                </div>
            </section>
		<?php endforeach ?>

        <div class="bf-floating-bar" x-show="editing" x-cloak>
            <div class="bf-floating-bar-inner bf-dashboard-editbar">
                <span class="bf-dashboard-editbar-text" x-show="! failed"><?= lang('Bonfire.customizing') ?></span>
                <span class="bf-dashboard-editbar-text text-danger" x-show="failed" role="alert"><?= lang('Bonfire.layoutFailed') ?></span>
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
