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

            <legend><i class="fas fa-chart-pie"></i></i> Doughnut Chart General Settings</legend>

            <p>In this section you can customize the general parameters concerning the Doughnut chart.</p>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="doughnut_showTitle" role="switch" id="doughnut_showTitle"
					<?php if (setting('DoughnutChart.doughnut_showTitle')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="doughnut_showTitle">Show the Title?</label>
            </div>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="doughnut_showLegend" role="switch" id="doughnut_showLegend"
					<?php if (setting('DoughnutChart.doughnut_showLegend')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="doughnut_showLegend">Show Legend?</label>
            </div>
            <div class="form-group col-12 col-sm-6 col-md-3">
                <label class="form-label" for="doughnut_legendPosition">Legend Position</label>
                <select name="doughnut_legendPosition" id="doughnut_legendPosition" class="form-select">
                    <option value="top" <?php if (old('doughnut_legendPosition', setting('DoughnutChart.doughnut_legendPosition')) === 'top') : ?> selected <?php endif?>>top</option>
                    <option value="left" <?php if (old('doughnut_legendPosition', setting('DoughnutChart.doughnut_legendPosition')) === 'left') : ?> selected <?php endif?>>left</option>
                    <option value="bottom"  <?php if (old('doughnut_legendPosition', setting('DoughnutChart.doughnut_legendPosition')) === 'bottom') : ?> selected <?php endif?>>bottom</option>
                    <option value="right"  <?php if (old('doughnut_legendPosition', setting('DoughnutChart.doughnut_legendPosition')) === 'right') : ?> selected <?php endif?>>right</option>
                </select>
            </div>
            <br/>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="doughnut_enableAnimation" role="switch" id="doughnut_enableAnimation"
					<?php if (setting('DoughnutChart.doughnut_enableAnimation')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="doughnut_enableAnimation">Enable Animation?</label>
            </div>

            <div class="form-group col-12 col-sm-6 col-md-3">
                <label class="form-label" for="doughnut_colorScheme">Assign a predefined color scheme to fill the chart</label>
                <select name="doughnut_colorScheme" id="doughnut_colorScheme" class="form-select" hx-post="schemePreview" hx-target="#doughnut_colorScheme_preview">
                    <option value="null" <?php if (old('doughnut_colorScheme', setting('DoughnutChart.doughnut_colorScheme')) === 'null') : ?> selected <?php endif?>>Default</option>
                    <option value="Blues" <?php if (old('doughnut_colorScheme', setting('DoughnutChart.doughnut_colorScheme')) === 'Blues') : ?> selected <?php endif?>>Blues</option>
                    <option value="Greens" <?php if (old('doughnut_colorScheme', setting('DoughnutChart.doughnut_colorScheme')) === 'Greens') : ?> selected <?php endif?>>Greens</option>
                    <option value="Greys"  <?php if (old('doughnut_colorScheme', setting('DoughnutChart.doughnut_colorScheme')) === 'Greys') : ?> selected <?php endif?>>Greys</option>
                    <option value="Oranges"  <?php if (old('doughnut_colorScheme', setting('DoughnutChart.doughnut_colorScheme')) === 'Oranges') : ?> selected <?php endif?>>Oranges</option>
                    <option value="Purples"  <?php if (old('doughnut_colorScheme', setting('DoughnutChart.doughnut_colorScheme')) === 'Purples') : ?> selected <?php endif?>>Purples</option>
                    <option value="Reds"  <?php if (old('doughnut_colorScheme', setting('DoughnutChart.doughnut_colorScheme')) === 'Reds') : ?> selected <?php endif?>>Reds</option>
                </select>
            </div>
            <br/>
            <div id="doughnut_colorScheme_preview" class="col-12 col-sm-6 col-md-3">
				<?php if (old('doughnut_colorScheme', setting('DoughnutChart.doughnut_colorScheme')) !== 'null') : ?>
                    <img src="/assets/admin/img/color_scheme/<?= setting('DoughnutChart.doughnut_colorScheme') ?>.png" style="height:40px !important; width:300px;"/>
				<?php endif ?>
            </div>
        </fieldset>

        <div class="text-end px-5 py-3">
            <input type="submit" value="Save Doughnut Chart Settings" class="btn btn-primary btn-lg">
        </div>
    </form>


</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
