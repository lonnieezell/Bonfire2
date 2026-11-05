<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc(lang('Content.editStructure')) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<?php $action = url_to('content-structure', $collection->handle) ?>
<x-page-head back="<?= url_to('content-list', $collection->handle) ?>" backLabel="<?= esc($collection->title, 'attr') ?>" title="<?= esc(lang('Content.editStructure'), 'attr') ?>" subtitle="<?= esc(lang('Content.structureHint'), 'attr') ?>" />

<?php if ($notice !== null) : ?>
    <div class="alert alert-danger" role="alert" data-notice>
        <?= esc($notice) ?>
        <?php if ($conflict) : ?>
            <a href="<?= $action ?>" data-reload><?= lang('Content.structureReload') ?></a>
        <?php endif ?>
    </div>
<?php endif ?>

<form action="<?= $action ?>" method="post" data-tree-form x-data="{ showTrashed: false }">
    <?= csrf_field() ?>
    <input type="hidden" name="hash" value="<?= esc($hash, 'attr') ?>">

    <x-admin-box>
        <?php if ($nodes === []) : ?>
            <x-empty-state title="<?= esc(lang('Content.structureEmpty'), 'attr') ?>" />
        <?php else : ?>
            <?php if ($trashed > 0) : ?>
                <button type="button" class="btn btn-sm btn-outline-secondary mb-3" data-toggle-trashed @click="showTrashed = ! showTrashed" :aria-pressed="showTrashed"><?= lang('Content.showTrashed', [$trashed]) ?></button>
            <?php endif ?>
            <ul class="list-group" data-tree>
                <?= view('Bonfire\Content\Views\entry_tree_nodes', ['nodes' => $nodes, 'entries' => $entries], ['saveData' => false]) ?>
            </ul>
        <?php endif ?>
    </x-admin-box>

    <x-save-bar save="<?= esc(lang('Content.saveStructure'), 'attr') ?>" />
</form>

<script>
    {
        const form = document.querySelector('[data-tree-form]');
        const root = form.querySelector('[data-tree]');

        if (root) {
            const read = (list) => [...list.children].map((item) => ({
                id: item.dataset.id,
                children: read(item.querySelector(':scope > [data-children]')),
            }));

            // The items have no named fields. This puts them in what's posted and in what the save bar compares,
            // which it first looks at when Alpine starts, after this script.
            form.addEventListener('formdata', (event) => event.formData.set('tree', JSON.stringify(read(root))));

            // Discarding resets the fields, but only opening the page again puts back the items that were moved.
            form.addEventListener('reset', () => location.assign(form.action));

            // SortableJS comes after the page's scripts, so wait for it.
            document.addEventListener('DOMContentLoaded', () => bfTreeSortable(root));
        }
    }
</script>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<?= view('Bonfire\Content\Views\tree_sortable', [], ['saveData' => false]) ?>
<?php $this->endSection() ?>
