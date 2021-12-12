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
                    <input class="form-check-input" type="checkbox" name="bar_enableAnimation" role="switch" id="bar_enableAnimation"
						<?php if (setting('BarChart.bar_enableAnimation')) : ?> checked <?php endif ?>
                    >
                    <label class="form-check-label" for="bar_enableAnimation">Enable Animation?</label>
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
                    <input class="form-check-input" type="checkbox" name="doughnut_enableAnimation" role="switch" id="doughnut_enableAnimation"
						<?php if (setting('DoughnutChart.doughnut_enableAnimation')) : ?> checked <?php endif ?>
                    >
                    <label class="form-check-label" for="doughnut_enableAnimation">Enable Animation?</label>
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
                    <input class="form-check-input" type="checkbox" name="pie_enableAnimation" role="switch" id="pie_enableAnimation"
						<?php if (setting('PieChart.pie_enableAnimation')) : ?> checked <?php endif ?>
                    >
                    <label class="form-check-label" for="pie_enableAnimation">Enable Animation?</label>
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
                    <input class="form-check-input" type="checkbox" name="polarArea_enableAnimation" role="switch" id="polarArea_enableAnimation"
						<?php if (setting('PolarAreaChart.polarArea_enableAnimation')) : ?> checked <?php endif ?>
                    >
                    <label class="form-check-label" for="polarArea_enableAnimation">Enable Animation?</label>
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
<script>

</script>
<?php $this->endSection() ?>
