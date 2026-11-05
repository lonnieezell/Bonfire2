<?php use Bonfire\Content\NavigationTree; ?>
<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc($navigation->title) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<?php $action = url_to('content-navigation', $navigation->handle) ?>
<x-page-head title="<?= esc($navigation->title, 'attr') ?>" subtitle="<?= esc(lang('Content.navigationHint'), 'attr') ?>" />

<?php if ($notice !== null) : ?>
    <div class="alert alert-danger" role="alert" data-notice>
        <?= esc($notice) ?>
        <a href="<?= $action ?>" data-reload><?= lang('Content.navigationReload') ?></a>
    </div>
<?php endif ?>

<x-admin-box>
    <div class="d-flex flex-wrap gap-4">
        <?php // Apart from the form that saves the menu, so typing here doesn't count as a change to it. ?>
        <?php if ($picker !== []) : ?>
        <form class="d-flex gap-2" data-add-entry>
            <select class="form-select form-select-sm w-auto" data-entry-picker aria-label="<?= esc(lang('Content.navigationAddEntry'), 'attr') ?>" required>
                <?php foreach ($picker as $group) : ?>
                    <optgroup label="<?= esc($group['title'], 'attr') ?>">
                        <?php foreach ($group['entries'] as $choice) : ?>
                            <option value="<?= esc($choice['id'], 'attr') ?>" data-title="<?= esc($choice['title'], 'attr') ?>"><?= esc($choice['title']) ?><?= $choice['hidden'] === null ? '' : ' (' . esc($choice['hidden']) . ')' ?></option>
                        <?php endforeach ?>
                    </optgroup>
                <?php endforeach ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary"><?= lang('Content.navigationAddEntry') ?></button>
        </form>
        <?php endif ?>
        <form class="d-flex gap-2" data-add-link>
            <input type="text" class="form-control form-control-sm w-auto" name="title" placeholder="<?= esc(lang('Content.navigationLinkTitle'), 'attr') ?>" aria-label="<?= esc(lang('Content.navigationLinkTitle'), 'attr') ?>" pattern=".*\S.*" required>
            <input type="text" class="form-control form-control-sm w-auto" name="url" placeholder="<?= esc(lang('Content.navigationLinkUrl'), 'attr') ?>" aria-label="<?= esc(lang('Content.navigationLinkUrl'), 'attr') ?>" pattern="<?= esc(NavigationTree::URL_PATTERN, 'attr') ?>" title="<?= esc(lang('Content.navigationLinkUrlHint'), 'attr') ?>" required>
            <button type="submit" class="btn btn-sm btn-outline-secondary"><?= lang('Content.navigationAddLink') ?></button>
        </form>
    </div>
</x-admin-box>

<form action="<?= $action ?>" method="post" data-tree-form>
    <?= csrf_field() ?>
    <input type="hidden" name="hash" value="<?= esc($hash, 'attr') ?>">

    <x-admin-box>
        <ul class="list-group" data-tree style="min-height: 2rem">
            <?= view('Bonfire\Content\Views\navigation_tree_nodes', ['nodes' => $nodes, 'entries' => $entries], ['saveData' => false]) ?>
        </ul>
    </x-admin-box>

    <x-save-bar save="<?= esc(lang('Content.saveNavigation'), 'attr') ?>" />
</form>

<template data-new-entry>
    <?= view('Bonfire\Content\Views\navigation_tree_nodes', ['nodes' => [['entry' => '', 'children' => []]], 'entries' => ['' => ['title' => '', 'hidden' => null]]], ['saveData' => false]) ?>
</template>
<template data-new-link>
    <?= view('Bonfire\Content\Views\navigation_tree_nodes', ['nodes' => [['url' => '', 'title' => '', 'children' => []]], 'entries' => []], ['saveData' => false]) ?>
</template>
<script>
    {
        const form = document.querySelector('[data-tree-form]');
        const root = form.querySelector('[data-tree]');
        const changed = () => form.dispatchEvent(new Event('input', { bubbles: true }));

        const read = (list) => [...list.children].map((item) => ({
            ...('entry' in item.dataset ? { entry: item.dataset.entry } : { url: item.querySelector('[data-url]').value.trim() }),
            title: item.querySelector('[data-title]').value,
            children: read(item.querySelector(':scope > [data-children]')),
        }));

        // The items have no named fields. This puts them in what's posted and in what the save bar compares,
        // which it first looks at when Alpine starts, after this script.
        form.addEventListener('formdata', (event) => event.formData.set('tree', JSON.stringify(read(root))));

        // Discarding resets the fields, but only opening the menu again puts back the items that were moved, added or removed.
        form.addEventListener('reset', () => location.assign(form.action));

        const add = (template, fill) => {
            const item = document.querySelector(template).content.querySelector('li').cloneNode(true);

            fill(item);
            root.append(item);
            bfTreeSortable(item.querySelector('[data-children]'));
            changed();
        };

        document.querySelector('[data-add-entry]')?.addEventListener('submit', (event) => {
            event.preventDefault();

            const option = event.target.querySelector('[data-entry-picker]').selectedOptions[0];

            add('[data-new-entry]', (item) => {
                item.dataset.entry = option.value;
                item.querySelector('[data-label]').textContent = option.textContent;
                item.querySelector('[data-title]').placeholder = option.dataset.title;
            });
        });

        document.querySelector('[data-add-link]').addEventListener('submit', (event) => {
            event.preventDefault();

            const { title, url } = event.target.elements;

            add('[data-new-link]', (item) => {
                item.querySelector('[data-title]').value = title.value;
                item.querySelector('[data-url]').value = url.value;
            });

            event.target.reset();
        });

        root.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove]');

            if (button) {
                button.closest('li').remove();
                changed();
            }
        });

        // SortableJS comes after the page's scripts, so wait for it.
        document.addEventListener('DOMContentLoaded', () => bfTreeSortable(root));
    }
</script>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<?= view('Bonfire\Content\Views\tree_sortable', [], ['saveData' => false]) ?>
<?php $this->endSection() ?>
