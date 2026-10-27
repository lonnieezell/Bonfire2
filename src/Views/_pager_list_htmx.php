<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * A list footer: "Showing X of Y" with Previous / Next, loaded through htmx.
 * Put hx-target (and any hx-include) on an ancestor.
 *
 * @var PagerRenderer $pager
 */
$pages = [
    'previous' => $pager->hasPreviousPage() ? $pager->getPreviousPage() : null,
    'next'     => $pager->hasNextPage() ? $pager->getNextPage() : null,
];
?>
<div class="bf-list-footer">
    <span><?= lang('Bonfire.showingOf', [$pager->getPerPageEnd() - $pager->getPerPageStart() + 1, $pager->getTotal()]) ?></span>
    <?php if ($pager->getPageCount() > 1) : ?>
        <nav class="bf-list-pager" aria-label="<?= lang('Pager.pageNavigation') ?>">
            <?php foreach ($pages as $label => $uri) : ?>
                <?php if ($uri !== null) : ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= $uri ?>" hx-get="<?= $uri ?>"><?= lang('Pager.' . $label) ?></a>
                <?php else : ?>
                    <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true"><?= lang('Pager.' . $label) ?></span>
                <?php endif ?>
            <?php endforeach ?>
        </nav>
    <?php endif ?>
</div>
