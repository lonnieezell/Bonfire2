<?php

use Bonfire\Widgets\WidgetOptions;

/**
 * The site defaults of one settings class: its widgets' fields, filled with what they inherit.
 *
 * @var array<string, WidgetOptions> $tabs
 * @var WidgetOptions                $options
 */
?>
<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-settings-layout>
    <?= view('Bonfire\Widgets\Views\_tabs', ['tab' => $options->alias, 'tabs' => $tabs]) ?>

    <form action="<?= site_url(ADMIN_AREA . '/settings/widgets') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="widget" value="<?= esc($options->alias, 'attr') ?>">

        <x-admin-box title="<?= esc($options->title, 'attr') ?>" description="<?= esc(lang('Widgets.defaultsHint'), 'attr') ?>">
            <?php foreach ($options->fields as $field) : ?>
                <?= view('Bonfire\Dashboard\Views\_widget_field', ['field' => $field, 'value' => $field->inherited()]) ?>
                <?php if ($field->name === 'colorScheme') : ?>
                    <div class="mb-3" hx-get="<?= site_url(ADMIN_AREA . '/settings/widgets/schemePreview') ?>" hx-include="#bf-widget-setting-colorScheme" hx-trigger="load, change from:#bf-widget-setting-colorScheme"></div>
                <?php endif ?>
            <?php endforeach ?>
        </x-admin-box>

        <x-save-bar save="Save Settings" />
    </form>
</x-settings-layout>
<?php $this->endSection() ?>
