<?php use Bonfire\Content\NavigationTree; ?>
<?php // One level of the menu editor. Each item has a list of its own, empty or not, so that others can be dropped into it. ?>
<?php foreach ($nodes as $node) : ?>
    <?php $isEntry = isset($node['entry']) ?>
    <?php $entry = $isEntry ? ($entries[$node['entry']] ?? null) : null ?>
    <li class="list-group-item"<?= $isEntry ? ' data-entry="' . esc($node['entry'], 'attr') . '"' : '' ?>>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <i class="fas fa-grip-vertical text-muted" data-handle title="<?= esc(lang('Content.structureHandle'), 'attr') ?>" style="cursor: grab"></i>
            <?php if ($isEntry) : ?>
                <span data-label><?= esc($entry['title'] ?? '') ?></span>
                <?php $hidden = $entry === null ? lang('Content.navigationMissing') : $entry['hidden'] ?>
                <?php if ($hidden !== null) : ?>
                    <span data-hidden><x-status tone="muted" label="<?= esc($hidden, 'attr') ?>" /></span>
                <?php endif ?>
                <input type="text" class="form-control form-control-sm w-auto" data-title value="<?= esc($node['title'] ?? '', 'attr') ?>" placeholder="<?= esc($entry['title'] ?? '', 'attr') ?>" aria-label="<?= esc(lang('Content.navigationTitleOverride'), 'attr') ?>">
            <?php else : ?>
                <input type="text" class="form-control form-control-sm w-auto" data-title value="<?= esc($node['title'], 'attr') ?>" placeholder="<?= esc(lang('Content.navigationLinkTitle'), 'attr') ?>" aria-label="<?= esc(lang('Content.navigationLinkTitle'), 'attr') ?>" pattern=".*\S.*" required>
                <input type="text" class="form-control form-control-sm w-auto" data-url value="<?= esc($node['url'], 'attr') ?>" placeholder="<?= esc(lang('Content.navigationLinkUrl'), 'attr') ?>" aria-label="<?= esc(lang('Content.navigationLinkUrl'), 'attr') ?>" pattern="<?= esc(NavigationTree::URL_PATTERN, 'attr') ?>" title="<?= esc(lang('Content.navigationLinkUrlHint'), 'attr') ?>" required>
            <?php endif ?>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-remove title="<?= esc(lang('Content.navigationRemove'), 'attr') ?>" aria-label="<?= esc(lang('Content.navigationRemove'), 'attr') ?>"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <ul class="list-group list-group-flush ms-4 mt-2" data-children style="min-height: 0.5rem">
            <?= view('Bonfire\Content\Views\navigation_tree_nodes', ['nodes' => $node['children'], 'entries' => $entries], ['saveData' => false]) ?>
        </ul>
    </li>
<?php endforeach ?>
