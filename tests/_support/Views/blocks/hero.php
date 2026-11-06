<?php
/**
 * @var Bonfire\Content\Block $block
 */
?>
<section data-block="hero"><h2><?= esc($block->field('heading', 'No heading')) ?></h2><x-greeting name="<?= esc((string) $block->field('heading', ''), 'attr') ?>" /></section>
