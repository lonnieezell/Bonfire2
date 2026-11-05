<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="<?= esc($collection->title, 'attr') ?>" count="<?= $pager->getTotal() ?>">
    <?php if ($canArrange) : ?>
        <a href="<?= url_to('content-structure', $collection->handle) ?>" class="btn btn-outline-secondary" data-structure><i class="fas fa-sitemap" aria-hidden="true"></i> <?= lang('Content.editStructure') ?></a>
    <?php endif ?>
    <?php if ($canEdit) : ?>
        <a href="<?= url_to('content-new', $collection->handle) ?>" class="btn btn-primary" data-new><i class="fas fa-plus" aria-hidden="true"></i> <?= lang('Content.newEntry') ?></a>
    <?php endif ?>
</x-page-head>

<x-admin-box>
    <?php if ($entries === []) : ?>
        <x-empty-state title="<?= esc(lang('Content.listEmpty'), 'attr') ?>" hint="<?= esc(lang('Content.listEmptyHint'), 'attr') ?>" />
    <?php else : ?>
        <div class="table-responsive">
            <table class="table bf-table">
                <thead>
                    <tr>
                        <th><?= lang('Content.colTitle') ?></th>
                        <th><?= lang('Content.colStatus') ?></th>
                        <th><?= lang('Content.colDate') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry) : ?>
                        <tr data-entry="<?= esc($entry->id, 'attr') ?>">
                            <td data-title>
                                <?php if ($canEdit) : ?>
                                    <a href="<?= url_to('content-edit', $collection->handle, $entry->id) ?>"><?= esc($entry->title) ?></a>
                                <?php else : ?>
                                    <?= esc($entry->title) ?>
                                <?php endif ?>
                            </td>
                            <td data-status="<?= esc($entry->status, 'attr') ?>">
                                <x-status tone="<?= $entry->status === 'published' ? 'ok' : 'muted' ?>" label="<?= esc(lang($entry->status === 'published' ? 'Content.statusPublished' : 'Content.statusDraft'), 'attr') ?>" />
                            </td>
                            <td class="bf-muted" data-date><?= $entry->publishedAt === null ? '' : app_date($entry->publishedAt, true) ?></td>
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
