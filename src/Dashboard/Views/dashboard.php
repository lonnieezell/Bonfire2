<?php $this->extend('master') ?>

<?php $this->section('styles') ?>

	<?= asset_link('admin/css/widgets.css', 'css'); ?>

<?php $this->endSection() ?>


<?php $this->section('main') ?>

	<?php foreach ($layout->sections as $section) : ?>
        <section class="bf-dashboard-section">
			<?php if ($section->title !== '') : ?>
                <h2 class="bf-dashboard-section-title"><?= esc($section->title) ?></h2>
			<?php endif ?>
            <div class="bf-dashboard-grid">
				<?php foreach ($section->widgets as $placement) : ?>
                    <div class="bf-widget bf-span-<?= $placement->size->columns() ?>" data-widget-id="<?= esc($placement->id, 'attr') ?>">
						<?= $dashboard->widget($placement->id)->render() ?>
                    </div>
				<?php endforeach ?>
            </div>
        </section>
	<?php endforeach ?>

<?php $this->endSection() ?>
