<?php $this->extend('master') ?>

<?php $this->section('main') ?>


<x-page-head>
    <x-module-title><i class="far fa-object-group"></i> Widgets</x-module-title>
    <h2>Settings</h2>
</x-page-head>

<?= view('Bonfire\Widgets\Views\_tabs', ['tab' => 'basics']) ?>

<x-admin-box>

    <form action="<?= site_url(ADMIN_AREA . '/settings/widgets') ?>" method="post">
		<?= csrf_field() ?>
        <fieldset class="first">

            <legend><i class="fas fa-object-group"></i> Widgets Settings</legend>

            <p>In this section you can manage widgets on the dashboard.</p>
            <br/>

			<?php foreach ($manager as $elem): ?>

                <div class="form-check form-switch mt-6 mb-3">
                    <input class="form-check-input" type="checkbox" name="<?= $elem['widget'] ?>_<?= $elem['id'] ?>" role="switch" id="<?= $elem['widget'] ?>_<?= $elem['id'] ?>"
                        <?php if (setting('Stats.' . $elem['widget'] . '_' . $elem['id'])) : ?> checked <?php endif ?>
                    >
                    <label class="form-check-label" for="<?= $elem['widget'] ?>_<?= $elem['id'] ?>">Enable <?= rtrim((string) $elem['widget'], 's') ?> <?= $elem['type'] ?? '' ?> widget "<?= $elem['title'] ?>"</label>
                </div>

			<?php endforeach; ?>
        </fieldset>
        <x-button-container>
            <x-button>Save Widget Settings</x-button>
        </x-button-container>
    </form>

    <form action="<?= site_url(ADMIN_AREA . '/settings/widgetsReset') ?>" method="post">
		<?= csrf_field() ?>
        <x-button-container>
            <x-button>Reset all settings of all widgets to their default values</x-button>
        </x-button-container>
    </form>
</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
