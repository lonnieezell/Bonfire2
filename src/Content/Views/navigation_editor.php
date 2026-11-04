<?php $this->extend('master') ?>

<?php $this->section('crumb') ?><?= esc(lang('Content.editNavigation')) ?><?php $this->endSection() ?>

<?php $this->section('main') ?>
<?php
$action = url_to('content-navigation', $navigation->handle);
// The collection is named in each choice only when there is more than one.
$several = count(array_unique(array_column($offered, 'collection'))) > 1;
?>
<x-page-head title="<?= esc($navigation->title, 'attr') ?>" subtitle="<?= esc(lang('Content.navigationHint'), 'attr') ?>" />

<?php if ($notice !== null) : ?>
    <div class="alert alert-danger" role="alert" data-notice>
        <?= esc($notice) ?>
        <?php if ($conflict) : ?>
            <a href="<?= $action ?>" data-reload><?= lang('Content.navigationReload') ?></a>
        <?php endif ?>
    </div>
<?php endif ?>

<form action="<?= $action ?>" method="post" data-tree-form>
    <?= csrf_field() ?>
    <input type="hidden" name="hash" value="<?= esc($hash, 'attr') ?>">
    <input type="hidden" name="tree" value="">

    <x-admin-box>
        <p class="text-muted mb-2" data-empty<?= $nodes === [] ? '' : ' hidden' ?>><?= lang('Content.navigationEmpty') ?></p>
        <ul class="list-group" data-tree>
            <?= view('Bonfire\Content\Views\navigation_nodes', ['nodes' => $nodes, 'known' => $known], ['saveData' => false]) ?>
        </ul>
    </x-admin-box>

    <x-admin-box>
        <div class="row g-3">
            <div class="col-md-6" data-add-entry>
                <label class="form-label" for="add-entry-select"><?= lang('Content.addEntry') ?></label>
                <div class="input-group">
                    <select class="form-select" id="add-entry-select" data-entry-select>
                        <option value=""><?= lang('Content.chooseEntry') ?></option>
                        <?php foreach ($offered as $entry) : ?>
                            <option value="<?= esc($entry['id'], 'attr') ?>"><?= esc($several ? "{$entry['title']} ({$entry['collection']})" : $entry['title']) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button type="button" class="btn btn-outline-secondary" data-add-entry-button><?= lang('Content.addEntryButton') ?></button>
                </div>
            </div>
            <div class="col-md-6" data-add-link>
                <label class="form-label" for="add-link-title"><?= lang('Content.addLink') ?></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="add-link-title" data-link-title placeholder="<?= esc(lang('Content.linkTitle'), 'attr') ?>">
                    <input type="text" class="form-control" data-link-url placeholder="<?= esc(lang('Content.linkUrl'), 'attr') ?>" aria-label="<?= esc(lang('Content.linkUrl'), 'attr') ?>">
                    <button type="button" class="btn btn-outline-secondary" data-add-link-button><?= lang('Content.addLinkButton') ?></button>
                </div>
            </div>
        </div>
    </x-admin-box>

    <x-save-bar save="<?= esc(lang('Content.saveNavigation'), 'attr') ?>" />

    <?php // A blank link, which the picker fills in and clones into the menu. ?>
    <template data-item-template>
        <?= view('Bonfire\Content\Views\navigation_nodes', ['nodes' => [['entry' => null, 'url' => '', 'title' => null, 'children' => []]], 'known' => []], ['saveData' => false]) ?>
    </template>
</form>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    // SortableJS comes after the page's scripts, so wait for it.
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-tree-form]');
        const root = form.querySelector('[data-tree]');
        const empty = form.querySelector('[data-empty]');
        const template = form.querySelector('[data-item-template]');
        const changed = () => form.dispatchEvent(new Event('input', { bubbles: true }));

        const sortable = (list) => {
            // Without SortableJS (a blocked CDN) the list can't be moved, but a save still posts it as it is.
            if (typeof Sortable !== 'undefined') {
                Sortable.create(list, {
                    group: 'bf-navigation-tree',
                    handle: '[data-handle]',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    onEnd: changed,
                });
            }
        };

        const refresh = () => { empty.hidden = root.children.length > 0; };

        for (const list of [root, ...root.querySelectorAll('[data-children]')]) {
            sortable(list);
        }

        // An item is a clone of the blank link, made into an entry by dropping the URL box.
        const add = (setup) => {
            const item = template.content.firstElementChild.cloneNode(true);

            setup(item);
            root.append(item);
            sortable(item.querySelector('[data-children]'));
            refresh();
            changed();
        };

        form.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove]');

            if (remove) {
                remove.closest('[data-item]').remove();
                refresh();
                changed();
            }
        });

        const addEntry = () => {
            const select = form.querySelector('[data-entry-select]');
            const option = select.selectedOptions[0];

            if (! option || option.value === '') {
                return;
            }

            add((item) => {
                const label = document.createElement('span');
                label.dataset.label = '';
                label.textContent = option.textContent;

                item.dataset.entry = option.value;
                item.querySelector('[data-url-input]').remove();
                item.querySelector('[data-title-input]').placeholder = option.textContent;
                item.querySelector('[data-handle]').after(label);
            });
            select.value = '';
        };

        const addLink = () => {
            const title = form.querySelector('[data-link-title]');
            const url = form.querySelector('[data-link-url]');

            if (title.value.trim() === '' || url.value.trim() === '') {
                return;
            }

            add((item) => {
                item.querySelector('[data-title-input]').value = title.value;
                item.querySelector('[data-url-input]').value = url.value;
            });
            title.value = '';
            url.value = '';
        };

        form.querySelector('[data-add-entry-button]').addEventListener('click', addEntry);
        form.querySelector('[data-add-link-button]').addEventListener('click', addLink);

        // Enter in a picker adds the item, and doesn't save the menu.
        form.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' || event.target.tagName !== 'INPUT' && event.target.tagName !== 'SELECT') {
                return;
            }

            event.preventDefault();

            if (event.target.closest('[data-add-entry]')) {
                addEntry();
            } else if (event.target.closest('[data-add-link]')) {
                addLink();
            }
        });

        const read = (list) => [...list.children].map((item) => {
            const row = item.querySelector(':scope > div');
            const node = { title: row.querySelector('[data-title-input]').value };

            if (item.dataset.entry) {
                node.entry = item.dataset.entry;
            } else {
                node.url = row.querySelector('[data-url-input]').value;
            }

            node.children = read(item.querySelector(':scope > [data-children]'));

            return node;
        });

        form.addEventListener('submit', () => {
            form.elements.tree.value = JSON.stringify(read(root));
        });
    });
</script>
<?php $this->endSection() ?>
