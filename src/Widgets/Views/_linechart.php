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

            <legend><i class="fas fa-chart-line"></i> Line Chart General Settings</legend>

            <p>In this section you can customize the general parameters concerning the line chart.</p>


            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="line_showTitle" role="switch" id="line_showTitle"
					<?php if (setting('LineChart.line_showTitle')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="line_showTitle">Show the Title?</label>
            </div>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="line_showLegend" role="switch" id="line_showLegend"
					<?php if (setting('LineChart.line_showLegend')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="line_showLegend">Show Legend?</label>
            </div>

            <div class="form-group col-12 col-sm-6 col-md-3">
                <label class="form-label" for="line_legendPosition">Legend Position</label>
                <select name="line_legendPosition" id="line_legendPosition" class="form-select">
                    <option value="top" <?php if (old('line_legendPosition', setting('LineChart.line_legendPosition')) === 'top') : ?> selected <?php endif?>>top</option>
                    <option value="left" <?php if (old('line_legendPosition', setting('LineChart.line_legendPosition')) === 'left') : ?> selected <?php endif?>>left</option>
                    <option value="bottom"  <?php if (old('line_legendPosition', setting('LineChart.line_legendPosition')) === 'bottom') : ?> selected <?php endif?>>bottom</option>
                    <option value="right"  <?php if (old('line_legendPosition', setting('LineChart.line_legendPosition')) === 'right') : ?> selected <?php endif?>>right</option>
                </select>
            </div>
            <br/>


            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="line_enableAnimation" role="switch" id="line_enableAnimation"
					<?php if (setting('LineChart.line_enableAnimation')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="line_enableAnimation">Enable Animation?</label>
            </div>


            <div class="row">
                <div class="col-3">
                    <label class="form-label" for="line_tension">The line tension</label><p>Bezier curve tension of the line. Set to 0 to draw straightlines.</p>
                    <input type="number" min="0" step="0.1" class="form-control col-2" name="line_tension"
                           value="<?= old('line_tension', setting('LineChart.line_tension')) ?>">
                </div>
            </div>


        </fieldset>

        <div class="text-end px-5 py-3">
            <input type="submit" value="Save Line Chart Settings" class="btn btn-primary btn-lg">
        </div>
    </form>


</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
