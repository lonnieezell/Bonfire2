<?php
/**
 * The input of a markdown field: a textarea, and a Preview tab that asks the server to render it.
 * Copy it to app/Views/content/fields/markdown.php to change it.
 *
 * @var Bonfire\Content\Field $field    `rows` in the blueprint sets its height
 * @var string                $name
 * @var string                $id
 * @var string                $value
 * @var bool                  $disabled
 * @var bool                  $isNew
 */
$rows = is_int($field->options['rows'] ?? null) ? $field->options['rows'] : 16;
?>
<div x-data="{ tab: 'write' }" data-markdown>
    <ul class="nav nav-tabs mb-2">
        <li class="nav-item">
            <button type="button" class="nav-link" :class="{ active: tab === 'write' }" @click="tab = 'write'" data-tab="write"><?= lang('Content.write') ?></button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link" :class="{ active: tab === 'preview' }" @click="tab = 'preview'" data-tab="preview"
                hx-post="<?= url_to('content-preview') ?>"
                hx-vals="js:{markdown: document.getElementById('<?= esc($id, 'js') ?>').value}"
                hx-target="#<?= esc($id, 'attr') ?>-preview"><?= lang('Content.preview') ?></button>
        </li>
    </ul>
    <?php // A browser drops the line break right after <textarea>, so an extra one keeps a value that starts with its own. ?>
    <textarea name="<?= esc($name, 'attr') ?>" id="<?= esc($id, 'attr') ?>" class="form-control" rows="<?= $rows ?>" x-show="tab === 'write'"<?= $disabled ? ' disabled' : '' ?>>
<?= esc($value) ?></textarea>
    <div id="<?= esc($id, 'attr') ?>-preview" class="border rounded p-3" x-show="tab === 'preview'" x-cloak data-preview></div>
</div>
