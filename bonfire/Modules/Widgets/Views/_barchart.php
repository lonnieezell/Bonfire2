<?php $this->extend('master') ?>

<?php $this->section('main') ?>


<x-page-head>
	<x-module-title><i class="far fa-object-group"></i> Widgets</x-module-title>
	<h2>Settings</h2>
</x-page-head>

<?= view('Bonfire\Modules\Widgets\Views\_tabs', ['tab' => $tab]) ?>

<x-admin-box>

	<form action="/admin/settings/widgets" method="post">
		<?= csrf_field() ?>
        <input type="hidden" id="widget" name="widget" value="<?=$tab?>">

        <fieldset>

            <legend><i class="fas fa-chart-bar"></i> Bar Chart General Settings</legend>

            <p>In this section you can customize the general parameters concerning the Bar chart.</p>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="bar_showTitle" role="switch" id="bar_showTitle"
					<?php if (setting('BarChart.bar_showTitle')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="bar_showTitle">Show the Title?</label>
            </div>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="bar_showLegend" role="switch" id="bar_showLegend"
					<?php if (setting('BarChart.bar_showLegend')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="bar_showLegend">Show Legend?</label>
            </div>

            <div class="form-group col-12 col-sm-6 col-md-3">
                <label class="form-label" for="bar_legendPosition">Legend Position</label>
                <select name="bar_legendPosition" id="bar_legendPosition" class="form-select">
                    <option value="top" <?php if (old('bar_legendPosition', setting('BarChart.bar_legendPosition')) === 'top') : ?> selected <?php endif?>>top</option>
                    <option value="left" <?php if (old('bar_legendPosition', setting('BarChart.bar_legendPosition')) === 'left') : ?> selected <?php endif?>>left</option>
                    <option value="bottom"  <?php if (old('bar_legendPosition', setting('BarChart.bar_legendPosition')) === 'bottom') : ?> selected <?php endif?>>bottom</option>
                    <option value="right"  <?php if (old('bar_legendPosition', setting('BarChart.bar_legendPosition')) === 'right') : ?> selected <?php endif?>>right</option>
                </select>
            </div>
            <br/>


            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="bar_enableAnimation" role="switch" id="bar_enableAnimation"
					<?php if (setting('BarChart.bar_enableAnimation')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="bar_enableAnimation">Enable Animation?</label>
            </div>

            <div class="form-group col-12 col-sm-6 col-md-3">
                <label class="form-label" for="bar_colorScheme">Assign a predefined color scheme to fill the chart</label>
                <!--select name="bar_colorScheme" id="bar_colorScheme" class="form-select" hx-post="widgets/schemePreview" hx-target="#bar_colorScheme_preview"-->
                <select name="bar_colorScheme" id="bar_colorScheme" class="form-select">
                    <option value="null" <?php if (old('bar_colorScheme', setting('BarChart.bar_colorScheme')) === 'null') : ?> selected <?php endif?>>Default</option>
                    <option value="Blues" <?php if (old('bar_colorScheme', setting('BarChart.bar_colorScheme')) === 'Blues') : ?> selected <?php endif?>>Blues</option>
                    <option value="Greens" <?php if (old('bar_colorScheme', setting('BarChart.bar_colorScheme')) === 'Greens') : ?> selected <?php endif?>>Greens</option>
                    <option value="Greys"  <?php if (old('bar_colorScheme', setting('BarChart.bar_colorScheme')) === 'Greys') : ?> selected <?php endif?>>Greys</option>
                    <option value="Oranges"  <?php if (old('bar_colorScheme', setting('BarChart.bar_colorScheme')) === 'Oranges') : ?> selected <?php endif?>>Oranges</option>
                    <option value="Purples"  <?php if (old('bar_colorScheme', setting('BarChart.bar_colorScheme')) === 'Purples') : ?> selected <?php endif?>>Purples</option>
                    <option value="Reds"  <?php if (old('bar_colorScheme', setting('BarChart.bar_colorScheme')) === 'Reds') : ?> selected <?php endif?>>Reds</option>
                </select>
            </div>
            <br/>
            <div id="bar_colorScheme_preview" class="col-12 col-sm-6 col-md-3">
				<?php if (old('bar_colorScheme', setting('BarChart.bar_colorScheme')) !== 'null') : ?>
                    <img src="/assets/admin/img/color_scheme/<?= setting('BarChart.bar_colorScheme') ?>.png" style="height:40px !important; width:300px;"/>
				<?php endif ?>
            </div>

        </fieldset>

		<div class="text-end px-5 py-3">
			<input type="submit" value="Save Settings" class="btn btn-primary btn-lg">
		</div>
	</form>


</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
