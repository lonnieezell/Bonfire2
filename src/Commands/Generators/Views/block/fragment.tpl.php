<@php
/**
 * @var Bonfire\Content\Block $block
 */
$fragment = $block->fragment();
?>
<@php if ($fragment !== null) : ?>
    <aside class="block-fragment">
        <@= $fragment->renderBlocks('sections') ?>
    </aside>
<@php endif ?>
