<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= esc(lang('Email.previewsModTitle'), 'attr') ?>"></x-page-head>

<?php if ($groups === []) : ?>
    <x-admin-box>
        <x-empty-state title="<?= esc(lang('Email.previewsEmpty'), 'attr') ?>" hint="<?= esc(lang('Email.previewsEmptyHint'), 'attr') ?>" />
    </x-admin-box>
<?php endif ?>

<?php foreach ($groups as $module => $mails) : ?>
    <x-admin-box title="<?= esc($module, 'attr') ?>">
        <div class="table-responsive" data-module="<?= esc($module, 'attr') ?>">
            <table class="table bf-table">
                <thead>
                    <tr>
                        <th><?= lang('Email.previewName') ?></th>
                        <th><?= lang('Email.previewSubject') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mails as $mail) : ?>
                        <tr data-mailable="<?= esc($mail['slug'], 'attr') ?>">
                            <td><a href="<?= url_to('mail-preview', $mail['slug']) ?>"><?= esc($mail['name']) ?></a></td>
                            <?php if ($mail['error'] !== null) : ?>
                                <td class="text-danger" data-error><?= esc($mail['error']) ?></td>
                            <?php else : ?>
                                <td data-subject><?= esc($mail['subject']) ?></td>
                            <?php endif ?>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </x-admin-box>
<?php endforeach ?>
<?php $this->endSection() ?>
