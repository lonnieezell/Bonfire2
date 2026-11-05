<?php
$titles  = array_column(Bonfire\Content\Collection::all(config('Content')->contentPath), 'title', 'handle');
$canEdit = auth()->user()->can('content.edit');
?>
<div class="table-responsive">
    <table class="table table-hover bf-table">
        <thead>
            <tr>
                <th><?= lang('Content.colTitle') ?></th>
                <th><?= lang('Content.colCollection') ?></th>
                <th><?= lang('Content.colStatus') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $entry) : ?>
            <?php $published = $entry['status'] === 'published' ?>
            <tr data-entry="<?= esc($entry['id'], 'attr') ?>">
                <td>
                    <?php if ($canEdit && isset($titles[$entry['collection']])) : ?>
                        <a href="<?= url_to('content-edit', $entry['collection'], $entry['id']) ?>"><?= esc($entry['title']) ?></a>
                    <?php else : ?>
                        <?= esc($entry['title']) ?>
                    <?php endif ?>
                </td>
                <td data-collection="<?= esc($entry['collection'], 'attr') ?>"><?= esc($titles[$entry['collection']] ?? $entry['collection']) ?></td>
                <td data-status="<?= esc($entry['status'], 'attr') ?>">
                    <x-status tone="<?= $published ? 'ok' : 'muted' ?>" label="<?= esc(lang($published ? 'Content.statusPublished' : 'Content.statusDraft'), 'attr') ?>" />
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
