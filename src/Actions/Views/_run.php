<?php
/**
 * A queued run's toast. It polls for its own replacement until the run ends.
 *
 * @var array<string, mixed> $run A row of action_runs
 */

use Bonfire\Actions\RunStatus;

?>
<?php if ($run['status']->ended()) : ?>
    <?= view(config('Alerts')->template, ['alerts' => [[$run['status'] === RunStatus::Failed ? 'danger' : 'success', esc($run['message'])]]]) ?>
<?php else : ?>
    <div class="alert d-flex" role="status" hx-get="<?= url_to('action-run', $run['id']) ?>" hx-trigger="every 2s" hx-swap="outerHTML">
        <span class="spinner-border spinner-border-sm mx-2 mt-1" aria-hidden="true"></span>
        <span class="alert-message"><?= lang('Actions.running', [$run['processed'] + $run['skipped'], $run['total']]) ?></span>
    </div>
<?php endif ?>
