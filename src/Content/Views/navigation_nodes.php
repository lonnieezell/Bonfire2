<?php // One level of the navigation editor. Each item has a list of its own, empty or not, so that others can be dropped into it. ?>
<?php foreach ($nodes as $node) : ?>
    <?php $isLink = $node['entry'] === null ?>
    <?php $entry = $isLink ? null : ($known[$node['entry']] ?? null) ?>
    <li class="list-group-item" data-item<?= $isLink ? '' : ' data-entry="' . esc($node['entry'], 'attr') . '"' ?>>
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-grip-vertical text-muted" data-handle title="<?= esc(lang('Content.structureHandle'), 'attr') ?>" style="cursor: grab"></i>
            <?php if ($entry !== null) : ?>
                <span data-label><?= esc($entry['title']) ?></span>
                <?php if ($entry['status'] !== 'published') : ?>
                    <x-status tone="muted" label="<?= esc(lang('Content.statusDraft'), 'attr') ?>" />
                <?php endif ?>
            <?php endif ?>
            <input type="text" class="form-control form-control-sm w-auto" data-title-input
                   value="<?= esc((string) $node['title'], 'attr') ?>"
                   placeholder="<?= esc($entry === null ? lang('Content.linkTitle') : $entry['title'], 'attr') ?>"
                   aria-label="<?= esc(lang('Content.itemTitle'), 'attr') ?>"
                   title="<?= esc($entry === null ? lang('Content.linkTitle') : lang('Content.itemTitleOverride'), 'attr') ?>">
            <?php if ($isLink) : ?>
                <input type="text" class="form-control form-control-sm w-auto" data-url-input
                       value="<?= esc((string) $node['url'], 'attr') ?>"
                       placeholder="<?= esc(lang('Content.linkUrl'), 'attr') ?>"
                       aria-label="<?= esc(lang('Content.linkUrl'), 'attr') ?>">
            <?php endif ?>
            <button type="button" class="btn btn-sm btn-outline-danger ms-auto" data-remove><?= lang('Content.removeItem') ?></button>
        </div>
        <ul class="list-group list-group-flush ms-4 mt-2" data-children style="min-height: 0.5rem">
            <?= view('Bonfire\Content\Views\navigation_nodes', ['nodes' => $node['children'], 'known' => $known], ['saveData' => false]) ?>
        </ul>
    </li>
<?php endforeach ?>
