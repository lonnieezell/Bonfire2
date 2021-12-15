<?php $this->extend('master') ?>

<?php $this->section('main') ?>


<x-page-head>
    <x-module-title><i class="far fa-object-group"></i> Widgets</x-module-title>
    <h2>Settings</h2>
</x-page-head>

<?= view('Bonfire\Modules\Widgets\Views\_tabs', ['tab' => 'basics']) ?>

<x-admin-box>

    <form action="/<?= ADMIN_AREA ?>/settings/widgets" method="post">
		<?= csrf_field() ?>
        <fieldset>

            <legend><i class="fas fa-object-group"></i> Widgets Settings</legend>

            <p>In this section you can manage widgets on the dashboard.</p>
            <br/>

			<?php foreach ($manager as $elem): ?>

                <div class="form-check form-switch mt-6 mb-3">
                    <input class="form-check-input" type="checkbox" name="<?= $elem['widget'] ?>_<?= $elem['index'] ?>" role="switch" id="<?= $elem['widget'] ?>_<?= $elem['index'] ?>"
						<?php if (setting('Stats.' . $elem['widget'] . '_' . $elem['index'])) : ?> checked <?php endif ?>
                    >
                    <label class="form-check-label" for="<?= $elem['widget'] ?>_<?= $elem['index'] ?>">Enable <?= rtrim($elem['widget'], 's') ?> <?= $elem['type'] ?? '' ?> widget "<?= $elem['title'] ?>"</label>
                </div>

			<?php endforeach; ?>
        </fieldset>
        <div class="text-end px-5 py-3">
            <input type="submit" value="Save Settings" class="btn btn-primary btn-lg">
        </div>
    </form>

    <form action="/<?= ADMIN_AREA ?>/settings/widgetsReset" method="post">
		<?= csrf_field() ?>
        <div class="text-end px-5 py-3">
            <input type="submit" value="Reset all settings of all widgets to their default values" class="btn btn-danger btn-lg">
        </div>
    </form>
</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
