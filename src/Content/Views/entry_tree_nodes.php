<?php // One level of the tree editor. Each entry has a list of its own, empty or not, so that others can be dropped into it. ?>
<?php foreach ($nodes as $node) : ?>
    <?php $entry = $entries[$node['id']] ?>
    <li class="list-group-item" data-id="<?= esc($node['id'], 'attr') ?>">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-grip-vertical text-muted" data-handle title="<?= esc(lang('Content.structureHandle'), 'attr') ?>" style="cursor: grab"></i>
            <span data-title><?= esc($entry['title']) ?></span>
            <?php if ($entry['status'] !== 'published') : ?>
                <x-status tone="muted" label="<?= esc(lang('Content.statusDraft'), 'attr') ?>" />
            <?php endif ?>
        </div>
        <ul class="list-group list-group-flush ms-4 mt-2" data-children style="min-height: 0.5rem">
            <?= view('Bonfire\Content\Views\entry_tree_nodes', ['nodes' => $node['children'], 'entries' => $entries], ['saveData' => false]) ?>
        </ul>
    </li>
<?php endforeach ?>
