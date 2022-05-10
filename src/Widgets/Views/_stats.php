<?php $this->extend('master') ?>

<?php $this->section('main') ?>


<x-page-head>
	<x-module-title><i class="far fa-object-group"></i> Widgets</x-module-title>
	<h2>Settings</h2>
</x-page-head>

<?= view('Bonfire\Widgets\Views\_tabs', ['tab' => $tab]) ?>

<x-admin-box>

	<form action="/<?= ADMIN_AREA ?>/settings/widgets" method="post">
		<?= csrf_field() ?>
        <input type="hidden" id="widget" name="widget" value="<?=$tab?>">

        <fieldset>

            <legend><i class="fas fa-chart-bar"></i> Stats General Settings</legend>

            <p>In this section you can customize the general parameters concerning the Stats widget.</p>

            <div class="form-check form-switch mt-6 mb-3">
                <input class="form-check-input" type="checkbox" name="stats_showLink" role="switch" id="stats_showLink"
					<?php if (setting('Stats.stats_showLink')) : ?> checked <?php endif ?>
                >
                <label class="form-check-label" for="stats_showLink">Show the Link "View Detail"?</label>
            </div>


        </fieldset>

		<div class="text-end px-5 py-3">
			<input type="submit" value="Save Stats Settings" class="btn btn-primary btn-lg">
		</div>
	</form>


</x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>

<?php $this->endSection() ?>
