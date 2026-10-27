<?php
// The options form of one chart type. $prefix is the field prefix ("bar"), $config the
// settings class ("BarChart"); $schemed charts pick a color scheme, $tension is for lines.
$value = static fn (string $option) => old("{$prefix}_{$option}", setting("{$config}.{$prefix}_{$option}"));
?>
<x-settings-layout>
    <?= view('Bonfire\Widgets\Views\_tabs', ['tab' => $tab]) ?>

    <form action="<?= site_url(ADMIN_AREA . '/settings/widgets') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" id="widget" name="widget" value="<?= esc($tab) ?>">

        <x-admin-box title="<?= esc($title) ?>">
            <x-setting-row label="Show the title" for="<?= $prefix ?>_showTitle">
                <x-switch name="<?= $prefix ?>_showTitle" value="on" offValue id="<?= $prefix ?>_showTitle" checked="<?= $value('showTitle') ? '1' : '0' ?>" />
            </x-setting-row>
            <x-setting-row label="Show the legend" for="<?= $prefix ?>_showLegend">
                <x-switch name="<?= $prefix ?>_showLegend" value="on" offValue id="<?= $prefix ?>_showLegend" checked="<?= $value('showLegend') ? '1' : '0' ?>" />
            </x-setting-row>
            <x-setting-row label="Legend position">
                <x-segmented name="<?= $prefix ?>_legendPosition" label="Legend position" value="<?= esc((string) $value('legendPosition')) ?>" options='{"top":"Top","left":"Left","bottom":"Bottom","right":"Right"}' />
            </x-setting-row>
            <x-setting-row label="Enable animation" for="<?= $prefix ?>_enableAnimation">
                <x-switch name="<?= $prefix ?>_enableAnimation" value="on" offValue id="<?= $prefix ?>_enableAnimation" checked="<?= $value('enableAnimation') ? '1' : '0' ?>" />
            </x-setting-row>
            <?php if ($tension ?? false) : ?>
                <x-setting-row label="Line tension" for="<?= $prefix ?>_tension" description="Bezier curve tension of the line. Set to 0 to draw straight lines.">
                    <input type="number" min="0" step="0.1" class="form-control bf-input-narrow" name="<?= $prefix ?>_tension" id="<?= $prefix ?>_tension" value="<?= esc((string) $value('tension')) ?>">
                </x-setting-row>
            <?php endif ?>
            <?php if ($schemed ?? false) : ?>
                <x-setting-row label="Color scheme" for="<?= $prefix ?>_colorScheme" description="A predefined color scheme to fill the chart.">
                    <select name="<?= $prefix ?>_colorScheme" id="<?= $prefix ?>_colorScheme" class="form-select" hx-post="schemePreview" hx-target="#<?= $prefix ?>_colorScheme_preview">
                        <?php foreach (['null' => 'Default', 'Blues' => 'Blues', 'Greens' => 'Greens', 'Greys' => 'Greys', 'Oranges' => 'Oranges', 'Purples' => 'Purples', 'Reds' => 'Reds'] as $scheme => $label) : ?>
                            <option value="<?= $scheme ?>"<?= $value('colorScheme') === $scheme ? ' selected' : '' ?>><?= $label ?></option>
                        <?php endforeach ?>
                    </select>
                    <div id="<?= $prefix ?>_colorScheme_preview" class="mt-2">
                        <?php if ($value('colorScheme') !== 'null') : ?>
                            <img src="/assets/admin/img/color_scheme/<?= esc((string) $value('colorScheme')) ?>.png" style="height:40px !important; width:300px;" alt=""/>
                        <?php endif ?>
                    </div>
                </x-setting-row>
            <?php endif ?>
        </x-admin-box>

        <x-save-bar save="Save Chart Settings" />
    </form>
</x-settings-layout>
