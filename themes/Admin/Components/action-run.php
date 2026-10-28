<?php // The toast of the action run just queued, if any. It polls until the run ends. ?>
<?php if (($runId = session('actionRun')) && ($run = model(Bonfire\Actions\Models\ActionRunModel::class)->find($runId))) : ?>
    <?= view('Bonfire\Actions\Views\_run', ['run' => $run]) ?>
<?php endif ?>
