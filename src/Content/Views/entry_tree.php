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

<form action="<?= $action ?>" method="post" data-tree-form>
    <?= csrf_field() ?>
    <input type="hidden" name="hash" value="<?= esc($hash, 'attr') ?>">
    <input type="hidden" name="tree" value="">

    <x-admin-box>
        <?php if ($nodes === []) : ?>
            <x-empty-state title="<?= esc(lang('Content.structureEmpty'), 'attr') ?>" />
        <?php else : ?>
            <ul class="list-group" data-tree>
                <?= view('Bonfire\Content\Views\entry_tree_nodes', ['nodes' => $nodes, 'entries' => $entries], ['saveData' => false]) ?>
            </ul>
        <?php endif ?>
    </x-admin-box>

    <x-save-bar save="<?= esc(lang('Content.saveStructure'), 'attr') ?>" />
</form>

<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    // SortableJS comes after the page's scripts, so wait for it.
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-tree-form]');
        const root = form.querySelector('[data-tree]');

        if (! root || typeof Sortable === 'undefined') {
            return;
        }

        for (const list of [root, ...root.querySelectorAll('[data-children]')]) {
            Sortable.create(list, {
                group: 'bf-content-tree',
                handle: '[data-handle]',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                // The save bar shows once the form changes.
                onEnd: () => form.dispatchEvent(new Event('input', { bubbles: true })),
            });
        }

        const read = (list) => [...list.children].map((item) => ({
            id: item.dataset.id,
            children: read(item.querySelector(':scope > [data-children]')),
        }));

        form.addEventListener('submit', () => {
            form.elements.tree.value = JSON.stringify(read(root));
        });
    });
</script>
<?php $this->endSection() ?>
