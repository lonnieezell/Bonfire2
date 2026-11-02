<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<?php $statusLabels = ['sent' => 'Email.outboxSent', 'failed' => 'Email.outboxFailed'] ?>
<x-page-head title="<?= esc(lang('Email.outboxModTitle'), 'attr') ?>" count="<?= $pager->getTotal() ?>"></x-page-head>

<x-admin-box>
    <form action="<?= url_to('mail-outbox') ?>" method="get" class="bf-toolbar-form mb-3" data-filters>
        <div class="row g-2 align-items-end">
            <div class="col-sm-6 col-lg-2">
                <label for="outboxStatus" class="form-label"><?= lang('Email.outboxStatus') ?></label>
                <select name="status" id="outboxStatus" class="form-select">
                    <option value=""><?= lang('Email.outboxAnyStatus') ?></option>
                    <?php foreach ($statusLabels as $value => $label) : ?>
                        <option value="<?= $value ?>"<?= ($filters['status'] ?? '') === $value ? ' selected' : '' ?>><?= lang($label) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label for="outboxMailer" class="form-label"><?= lang('Email.outboxMailer') ?></label>
                <select name="mailer" id="outboxMailer" class="form-select">
                    <option value=""><?= lang('Email.outboxAnyMailer') ?></option>
                    <?php foreach ($mailers as $mailer) : ?>
                        <option value="<?= esc($mailer, 'attr') ?>"<?= ($filters['mailer'] ?? '') === $mailer ? ' selected' : '' ?>><?= esc($mailer) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-sm-12 col-lg-3">
                <label for="outboxRecipient" class="form-label"><?= lang('Email.outboxRecipient') ?></label>
                <input type="search" name="recipient" id="outboxRecipient" class="form-control" value="<?= esc($filters['recipient'] ?? '', 'attr') ?>">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label for="outboxFrom" class="form-label"><?= lang('Email.outboxFrom') ?></label>
                <input type="date" name="date_from" id="outboxFrom" class="form-control" value="<?= esc($filters['date_from'] ?? '', 'attr') ?>">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label for="outboxTo" class="form-label"><?= lang('Email.outboxTo') ?></label>
                <input type="date" name="date_to" id="outboxTo" class="form-control" value="<?= esc($filters['date_to'] ?? '', 'attr') ?>">
            </div>
            <div class="col-12 col-lg-1 d-flex gap-2">
                <x-button><?= lang('Email.outboxFilter') ?></x-button>
                <?php if ($filters !== []) : ?>
                    <a href="<?= url_to('mail-outbox') ?>" class="btn btn-outline-secondary" data-reset><?= lang('Email.outboxReset') ?></a>
                <?php endif ?>
            </div>
        </div>
    </form>

    <?php if ($rows === []) : ?>
        <x-empty-state title="<?= esc(lang('Email.outboxEmpty'), 'attr') ?>" hint="<?= esc(lang('Email.outboxEmptyHint'), 'attr') ?>" />
    <?php else : ?>
        <div class="table-responsive">
            <table class="table bf-table">
                <thead>
                    <tr>
                        <th><?= lang('Email.outboxDate') ?></th>
                        <th><?= lang('Email.outboxStatus') ?></th>
                        <th><?= lang('Email.outboxRecipients') ?></th>
                        <th><?= lang('Email.outboxSubject') ?></th>
                        <th class="d-none d-lg-table-cell"><?= lang('Email.outboxMailer') ?></th>
                        <th class="d-none d-lg-table-cell"><?= lang('Email.outboxMailable') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr data-row="<?= (int) $row['id'] ?>">
                            <td class="bf-muted" data-date><?= app_date($row['created_at'], true) ?></td>
                            <td data-status="<?= esc($row['status'], 'attr') ?>">
                                <x-status tone="<?= $row['status'] === 'sent' ? 'ok' : 'danger' ?>" label="<?= esc(lang($statusLabels[$row['status']] ?? $statusLabels['failed']), 'attr') ?>" />
                            </td>
                            <td data-recipients>
                                <?php foreach (['to' => null, 'cc' => 'Email.outboxCc', 'bcc' => 'Email.outboxBcc'] as $field => $label) : ?>
                                    <?php if (($row[$field] ?? []) !== []) : ?>
                                        <div><?php if ($label !== null) : ?><span class="bf-muted"><?= lang($label) ?></span> <?php endif ?><?= esc(implode(', ', $row[$field])) ?></div>
                                    <?php endif ?>
                                <?php endforeach ?>
                                <?php if (($row['attachments'] ?? []) !== []) : ?>
                                    <div class="bf-muted"><i class="fas fa-paperclip" aria-hidden="true"></i> <?= esc(implode(', ', $row['attachments'])) ?></div>
                                <?php endif ?>
                            </td>
                            <td>
                                <span data-subject><?= esc((string) $row['subject']) ?></span>
                                <?php if ($row['error'] !== null) : ?>
                                    <div class="text-danger small" data-error><?= esc($row['error']) ?></div>
                                <?php endif ?>
                            </td>
                            <td class="d-none d-lg-table-cell" data-mailer><?= esc((string) $row['mailer']) ?><?php if ($row['transport'] !== null && $row['transport'] !== $row['mailer']) : ?> <span class="bf-muted">(<?= esc($row['transport']) ?>)</span><?php endif ?></td>
                            <td class="d-none d-lg-table-cell" data-mailable>
                                <?php if ($row['mailable_class'] !== null) : ?>
                                    <?php $name = esc(substr((string) strrchr('\\' . $row['mailable_class'], '\\'), 1)) ?>
                                    <?php if (isset($previews[$row['mailable_class']])) : ?>
                                        <a href="<?= url_to('mail-preview', $previews[$row['mailable_class']]) ?>" data-preview-link title="<?= esc($row['mailable_class'], 'attr') ?>"><?= $name ?></a>
                                    <?php else : ?>
                                        <span title="<?= esc($row['mailable_class'], 'attr') ?>"><?= $name ?></span>
                                    <?php endif ?>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div class="bf-list-footer">
            <?= $pager->links('default', 'bonfire_full') ?>
        </div>
    <?php endif ?>
</x-admin-box>
<?php $this->endSection() ?>
