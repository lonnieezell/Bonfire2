<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <?= view('Bonfire\Widgets\Views\_tabs', ['tab' => 'basics']) ?>

    <form action="<?= site_url(ADMIN_AREA . '/settings/widgets') ?>" method="post">
        <?= csrf_field() ?>

        <x-admin-box title="Available widgets" description="Choose which widgets are available to add to the dashboard. Switched-off widgets are left out of every palette and layout.">
            <?php foreach ($items as $item): ?>
                <?php $settings = $item->settings() ?>
                <x-setting-row label="<?= esc('Enable ' . rtrim($settings->kind->value, 's') . ' ' . (method_exists($item, 'type') ? $item->type() . ' ' : '') . 'widget "' . $item->title() . '"') ?>" for="<?= $settings->fieldName() ?>">
                    <x-switch name="<?= $settings->fieldName() ?>" value="on" offValue id="<?= $settings->fieldName() ?>" checked="<?= $settings->enabled() ? '1' : '0' ?>" />
                </x-setting-row>
            <?php endforeach ?>
        </x-admin-box>

        <x-save-bar save="Save Widget Settings" />
    </form>

    <x-admin-box title="Reset">
        <form action="<?= url_to('widgets-reset-layout') ?>" method="post">
            <?= csrf_field() ?>
            <x-setting-row label="Reset site default dashboard" description="Users who have not customized their dashboard see the default layout again.">
                <button type="submit" class="btn btn-outline-danger"
                        data-confirm="Reset the site default dashboard? Users' own layouts are kept." data-confirm-button="Reset" data-confirm-danger="1">Reset site default</button>
            </x-setting-row>
        </form>
        <form action="<?= site_url(ADMIN_AREA . '/settings/widgetsReset') ?>" method="post" x-data="{ confirming: false }">
            <?= csrf_field() ?>
            <x-setting-row label="Reset all widget settings" description="Every widget goes back to its default settings.">
                <button type="button" class="btn btn-outline-danger" x-show="! confirming" @click="confirming = true">Reset…</button>
                <noscript><button type="submit" class="btn btn-outline-danger">Reset</button></noscript>
                <span x-show="confirming" x-cloak class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" @click="confirming = false">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reset everything</button>
                </span>
            </x-setting-row>
        </form>
    </x-admin-box>
</x-settings-layout>
<?php $this->endSection() ?>
