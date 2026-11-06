<?php
/**
 * @var Bonfire\Content\Block $block
 */
?>
<a data-block="cta" data-entry="<?= esc($block->entry->slug, 'attr') ?>" data-new-tab="<?= var_export($block->field('new_tab'), true) ?>" data-count="<?= var_export($block->field('count'), true) ?>" href="#"><?= esc($block->field('label')) ?></a>
