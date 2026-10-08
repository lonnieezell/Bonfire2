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
            <br>

            <?php foreach ($items as $item): ?>
                <?php $settings = $item->settings() ?>

                <div class="form-check form-switch mt-6 mb-3">
                    <input class="form-check-input" type="checkbox" name="<?= $settings->fieldName() ?>" role="switch" id="<?= $settings->fieldName() ?>"
                        <?php if ($settings->enabled()) : ?> checked <?php endif ?>
                    >
                    <label class="form-check-label" for="<?= $settings->fieldName() ?>">Enable <?= rtrim($settings->kind->value, 's') ?> <?= method_exists($item, 'type') ? $item->type() : '' ?> widget "<?= $item->title() ?>"</label>
                </div>

            <?php endforeach; ?>
        </fieldset>
        <div class="text-end px-0 px-md-5">
            <x-button>Save Widget Settings</x-button>
        </div>
    </form>

    <x-button-container>
        <form action="<?= site_url(ADMIN_AREA . '/settings/widgetsReset') ?>" method="post">
            <?= csrf_field() ?>
            <x-button>Reset all settings of all widgets to their default values</x-button>
        </form>
    </x-button-container>
</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>