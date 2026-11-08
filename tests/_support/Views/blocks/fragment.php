<?php
/**
 * @var Bonfire\Content\Block $block
 */
$fragment = $block->fragment();
?>
<div data-block="fragment"><?php if ($fragment !== null) : ?><p data-fragment="<?= esc($fragment->slug, 'attr') ?>"><?= esc($fragment->title) ?></p><?= $fragment->renderBlocks('sections') ?><?php endif ?></div>
