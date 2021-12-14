<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <x-module-title><i class="far fa-object-group"></i> Widgets</x-module-title>
    <h2>Settings</h2>
</x-page-head>

<x-admin-box>
    <form action="/admin/settings/widgets" method="post">
		<?= csrf_field() ?>

        <fieldset>

            <legend><i class="fas fa-chart-line"></i> Line Chart Custom Settings</legend>

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

            <br/>
            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="useCustomSettings" role="switch" id="useCustomSettings"
					<?php if (setting('LineChart.useCustomSettings')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="useCustomSettings">Use advanced chart line settings?</label>
            </div>
			<?php if (setting('LineChart.useCustomSettings')) : ?>


                <div class="form-check form-switch mt-6 mb-3">
                    <div class="row">
                        <div class="col-3">
                            <label class="form-label" for="line_borderColor">The Line color</label>
                            <input type="color" class="form-control col-2"  name="line_borderColor"
                                   value="<?= old('line_borderColor', setting('LineChart.line_borderColor')) ?>" >
                        </div>
                    </div>
                </div>

                <div class="form-check form-switch mt-6 mb-3">
                    <div class="row">
                        <div class="col-3">
                            <label class="form-label" for="line_borderWidth">The Line width</label>
                            <input type="number" min="0" step="1" class="form-control col-2" name="line_borderWidth"
                                   value="<?= old('line_borderWidth', setting('LineChart.line_borderWidth')) ?>">
                        </div>
                    </div>
                </div>


			<?php endif ?>
        </fieldset>

        <fieldset>

            <legend><i class="fas fa-chart-bar"></i> Bar Chart Custom Settings</legend>

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
                <label class="form-label" for="bar_colorScheme">Assign Default Color Scheme</label>
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

        <fieldset>

            <legend><i class="fas fa-chart-pie"></i></i> Doughnut Chart Custom Settings</legend>

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
                <label class="form-label" for="doughnut_colorScheme">Assign Default Color Scheme</label>
                <select name="doughnut_colorScheme" id="doughnut_colorScheme" class="form-select">
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

        <fieldset>

            <legend><i class="fas fa-chart-pie"></i></i> Pie Chart Custom Settings</legend>

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
                <label class="form-label" for="pie_colorScheme">Assign Default Color Scheme</label>
                <select name="pie_colorScheme" id="pie_colorScheme" class="form-select">
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

        <fieldset>

            <legend><i class="fas fa-chart-pie"></i></i> Polar Chart Custom Settings</legend>

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
                <label class="form-label" for="polarArea_colorScheme">Assign Default Color Scheme</label>
                <select name="polarArea_colorScheme" id="polarArea_colorScheme" class="form-select">
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
            <input type="submit" value="Save Settings" class="btn btn-primary btn-lg">
        </div>
    </form>

    <form action="/admin/settings/widgetsReset" method="post">
        <div class="text-end px-5 py-3">
            <input type="submit" value="Reset Settings to Default" class="btn btn-danger btn-lg">
        </div>
    </form>
</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
