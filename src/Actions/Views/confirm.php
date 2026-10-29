<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<?php
/**
 * The confirmation without JavaScript: the same question <x-confirm-dialog> asks, as a page.
 *
 * @var Bonfire\Actions\Action  $action
 * @var Bonfire\Actions\Confirm $confirm
 * @var list<int|string>        $ids
 * @var bool                    $miscount
 */
?>
<x-page-head title="<?= esc($action->label) ?>"></x-page-head>

<x-admin-box>
    <form action="<?= current_url() ?>" method="post" class="bf-confirm-page">
        <?= csrf_field() ?>
        <input type="hidden" name="confirmed" value="1">
        <?php foreach ($ids as $id) : ?>
            <input type="hidden" name="selects[]" value="<?= esc($id) ?>">
        <?php endforeach ?>

        <h2 class="h5"><?= esc($confirm->title(count($ids))) ?></h2>
        <?php if ($confirm->text() !== '') : ?>
            <p><?= esc($confirm->text()) ?></p>
        <?php endif ?>

        <?php if ($confirm->asksToType()) : ?>
            <label class="form-label" for="confirm_count"><?= lang('Actions.typeCount', [count($ids)]) ?></label>
            <input type="text" inputmode="numeric" autocomplete="off" required id="confirm_count" name="confirm_count"
                class="form-control<?= $miscount ? ' is-invalid' : '' ?>">
            <?php if ($miscount) : ?>
                <div class="invalid-feedback"><?= lang('Actions.wrongCount') ?></div>
            <?php endif ?>
        <?php endif ?>

        <div class="d-flex gap-2 mt-3">
            <a href="<?= esc(previous_url()) ?>" class="btn btn-outline-secondary"><?= lang('Actions.cancel') ?></a>
            <button type="submit" class="btn <?= $action->danger ? 'btn-danger' : 'btn-primary' ?>">
                <?= esc($confirm->buttonLabel() ?: $action->label) ?>
            </button>
        </div>
    </form>
</x-admin-box>
<?php $this->endSection() ?>
