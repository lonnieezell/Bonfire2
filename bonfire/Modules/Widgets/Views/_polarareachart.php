<?php $this->extend('master') ?>

<?php $this->section('main') ?>


<x-page-head>
    <x-module-title><i class="far fa-object-group"></i> Widgets</x-module-title>
    <h2>Settings</h2>
</x-page-head>

<?= view('Bonfire\Modules\Widgets\Views\_tabs', ['tab' => $tab]) ?>

<x-admin-box>

    <form action="/<?= ADMIN_AREA ?>/settings/widgets" method="post">
		<?= csrf_field() ?>
        <input type="hidden" id="widget" name="widget" value="<?=$tab?>">

        <fieldset>

            <legend><i class="fas fa-chart-pie"></i></i> Polar Chart General Settings</legend>

            <p>In this section you can customize the general parameters concerning the Polar chart.</p>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="polarArea_showTitle" role="switch" id="polarArea_showTitle"
					<?php if (setting('PolarAreaChart.polarArea_showTitle')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="polarArea_showTitle">Show the Title?</label>
            </div>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="polarArea_showLegend" role="switch" id="polarArea_showLegend"
					<?php if (setting('PolarAreaChart.polarArea_showLegend')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="polarArea_showLegend">Show Legend?</label>
            </div>
            <div class="form-group col-12 col-sm-6 col-md-3">
                <label class="form-label" for="polarArea_legendPosition">Legend Position</label>
                <select name="polarArea_legendPosition" id="polarArea_legendPosition" class="form-select">
                    <option value="top" <?php if (old('polarArea_legendPosition', setting('PolarAreaChart.polarArea_legendPosition')) === 'top') : ?> selected <?php endif?>>top</option>
                    <option value="left" <?php if (old('polarArea_legendPosition', setting('PolarAreaChart.polarArea_legendPosition')) === 'left') : ?> selected <?php endif?>>left</option>
                    <option value="bottom"  <?php if (old('polarArea_legendPosition', setting('PolarAreaChart.polarArea_legendPosition')) === 'bottom') : ?> selected <?php endif?>>bottom</option>
                    <option value="right"  <?php if (old('polarArea_legendPosition', setting('PolarAreaChart.polarArea_legendPosition')) === 'right') : ?> selected <?php endif?>>right</option>
                </select>
            </div>
            <br/>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="polarArea_enableAnimation" role="switch" id="polarArea_enableAnimation"
					<?php if (setting('PolarAreaChart.polarArea_enableAnimation')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="polarArea_enableAnimation">Enable Animation?</label>
            </div>

            <div class="form-group col-12 col-sm-6 col-md-3">
                <label class="form-label" for="polarArea_colorScheme">Assign a predefined color scheme to fill the chart</label>
                <select name="polarArea_colorScheme" id="polarArea_colorScheme" class="form-select" hx-post="schemePreview" hx-target="#polarArea_colorScheme_preview">
                    <option value="null" <?php if (old('polarArea_colorScheme', setting('PolarAreaChart.polarArea_colorScheme')) === 'null') : ?> selected <?php endif?>>Default</option>
                    <option value="Blues" <?php if (old('polarArea_colorScheme', setting('PolarAreaChart.polarArea_colorScheme')) === 'Blues') : ?> selected <?php endif?>>Blues</option>
                    <option value="Greens" <?php if (old('polarArea_colorScheme', setting('PolarAreaChart.polarArea_colorScheme')) === 'Greens') : ?> selected <?php endif?>>Greens</option>
                    <option value="Greys"  <?php if (old('polarArea_colorScheme', setting('PolarAreaChart.polarArea_colorScheme')) === 'Greys') : ?> selected <?php endif?>>Greys</option>
                    <option value="Oranges"  <?php if (old('polarArea_colorScheme', setting('PolarAreaChart.polarArea_colorScheme')) === 'Oranges') : ?> selected <?php endif?>>Oranges</option>
                    <option value="Purples"  <?php if (old('polarArea_colorScheme', setting('PolarAreaChart.polarArea_colorScheme')) === 'Purples') : ?> selected <?php endif?>>Purples</option>
                    <option value="Reds"  <?php if (old('polarArea_colorScheme', setting('PolarAreaChart.polarArea_colorScheme')) === 'Reds') : ?> selected <?php endif?>>Reds</option>
                </select>
            </div>
            <br/>
            <div id="polarArea_colorScheme_preview" class="col-12 col-sm-6 col-md-3">
				<?php if (old('polarArea_colorScheme', setting('PolarAreaChart.polarArea_colorScheme')) !== 'null') : ?>
                    <img src="/assets/admin/img/color_scheme/<?= setting('PolarAreaChart.polarArea_colorScheme') ?>.png" style="height:40px !important; width:300px;"/>
				<?php endif ?>
            </div>
        </fieldset>

        <div class="text-end px-5 py-3">
            <input type="submit" value="Save Polar Area Chart Settings" class="btn btn-primary btn-lg">
        </div>
    </form>


</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
