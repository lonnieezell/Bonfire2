<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<?php
/**
 * A queued run's progress as a page, for when its toast is opened as a link.
 *
 * @var array<string, mixed> $run   A row of action_runs
 * @var string               $label The action's label
 */
?>
<x-page-head title="<?= esc($label) ?>"></x-page-head>

<x-admin-box>
    <p>
        <x-status tone="<?= $run['status']->tone() ?>" label="<?= esc($run['status']->label()) ?>" />
        <?= esc($run['list']) ?> · <?= $run['processed'] + $run['skipped'] ?> / <?= $run['total'] ?> ·
        <time datetime="<?= $run['created_at']->format('c') ?>"><?= esc($run['created_at']->humanize()) ?></time>
    </p>
    <?= view('Bonfire\Actions\Views\_run', ['run' => $run]) ?>
</x-admin-box>
<?php $this->endSection() ?>
