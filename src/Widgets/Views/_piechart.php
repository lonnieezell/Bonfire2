<?php $this->extend('master') ?>

<?php $this->section('main') ?>


<x-page-head>
    <x-module-title><i class="far fa-object-group"></i> Widgets</x-module-title>
    <h2>Settings</h2>
</x-page-head>

<?= view('Bonfire\Widgets\Views\_tabs', ['tab' => $tab]) ?>

<x-admin-box>

    <form action="<?= site_url(ADMIN_AREA . '/settings/widgets') ?>" method="post">
		<?= csrf_field() ?>
        <input type="hidden" id="widget" name="widget" value="<?=$tab?>">

        <fieldset class="first">

            <legend><i class="fas fa-chart-pie"></i></i> Pie Chart General Settings</legend>

            <p>In this section you can customize the general parameters concerning the Pie chart.</p>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="pie_showTitle" role="switch" id="pie_showTitle"
					<?php if (setting('PieChart.pie_showTitle')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="pie_showTitle">Show the Title?</label>
            </div>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="pie_showLegend" role="switch" id="pie_showLegend"
					<?php if (setting('PieChart.pie_showLegend')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="pie_showLegend">Show Legend?</label>
            </div>
            <div class="form-group col-12 col-sm-6 col-md-3">
                <label class="form-label" for="pie_legendPosition">Legend Position</label>
                <select name="pie_legendPosition" id="pie_legendPosition" class="form-select">
                    <option value="top" <?php if (old('pie_legendPosition', setting('PieChart.pie_legendPosition')) === 'top') : ?> selected <?php endif?>>top</option>
                    <option value="left" <?php if (old('pie_legendPosition', setting('PieChart.pie_legendPosition')) === 'left') : ?> selected <?php endif?>>left</option>
                    <option value="bottom"  <?php if (old('pie_legendPosition', setting('PieChart.pie_legendPosition')) === 'bottom') : ?> selected <?php endif?>>bottom</option>
                    <option value="right"  <?php if (old('pie_legendPosition', setting('PieChart.pie_legendPosition')) === 'right') : ?> selected <?php endif?>>right</option>
                </select>
            </div>
            <br/>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="pie_enableAnimation" role="switch" id="pie_enableAnimation"
					<?php if (setting('PieChart.pie_enableAnimation')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="pie_enableAnimation">Enable Animation?</label>
            </div>

            <div class="form-group col-12 col-sm-6 col-md-3">
                <label class="form-label" for="pie_colorScheme">Assign a predefined color scheme to fill the chart</label>
                <select name="pie_colorScheme" id="pie_colorScheme" class="form-select" hx-post="schemePreview" hx-target="#pie_colorScheme_preview">
                    <option value="null" <?php if (old('pie_colorScheme', setting('PieChart.pie_colorScheme')) === 'null') : ?> selected <?php endif?>>Default</option>
                    <option value="Blues" <?php if (old('pie_colorScheme', setting('PieChart.pie_colorScheme')) === 'Blues') : ?> selected <?php endif?>>Blues</option>
                    <option value="Greens" <?php if (old('pie_colorScheme', setting('PieChart.pie_colorScheme')) === 'Greens') : ?> selected <?php endif?>>Greens</option>
                    <option value="Greys"  <?php if (old('pie_colorScheme', setting('PieChart.pie_colorScheme')) === 'Greys') : ?> selected <?php endif?>>Greys</option>
                    <option value="Oranges"  <?php if (old('pie_colorScheme', setting('PieChart.pie_colorScheme')) === 'Oranges') : ?> selected <?php endif?>>Oranges</option>
                    <option value="Purples"  <?php if (old('pie_colorScheme', setting('PieChart.pie_colorScheme')) === 'Purples') : ?> selected <?php endif?>>Purples</option>
                    <option value="Reds"  <?php if (old('pie_colorScheme', setting('PieChart.pie_colorScheme')) === 'Reds') : ?> selected <?php endif?>>Reds</option>
                </select>
            </div>
            <br/>
            <div id="pie_colorScheme_preview" class="col-12 col-sm-6 col-md-3">
				<?php if (old('pie_colorScheme', setting('PieChart.pie_colorScheme')) !== 'null') : ?>
                    <img src="/assets/admin/img/color_scheme/<?= setting('PieChart.pie_colorScheme') ?>.png" style="height:40px !important; width:300px;"/>
				<?php endif ?>
            </div>
        </fieldset>

        <div class="text-end px-5 py-3">
            <input type="submit" value="Save Pie Chart Settings" class="btn btn-primary btn-lg">
        </div>
    </form>


</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
