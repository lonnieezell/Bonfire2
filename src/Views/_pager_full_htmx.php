<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>

<?php if ($pager->getPageCount() > 1) : ?>
<nav aria-label="<?= lang('Pager.pageNavigation') ?>" class="d-flex justify-content-center">
    <ul class="pagination" hx-target="#content-list">
        <?php if ($pager->hasPrevious()) : ?>
            <li class="page-item">
                <a href="<?= $pager->getFirst() ?>" hx-get="<?= $pager->getFirst() ?>" aria-label="<?= lang('Pager.first') ?>" class="page-link">
                    <span aria-hidden="true"><?= lang('Pager.first') ?></span>
                </a>
            </li>
            <li class="page-item">
                <a href="<?= $pager->getPrevious() ?>" hx-get="<?= $pager->getPrevious() ?>" aria-label="<?= lang('Pager.previous') ?>" class="page-link">
                    <span aria-hidden="true"><?= lang('Pager.previous') ?></span>
                </a>
            </li>
        <?php endif ?>

        <?php foreach ($pager->links() as $link) : ?>
            <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                <a href="<?= $link['uri'] ?>" hx-get="<?= $link['uri'] ?>" class="page-link">
                    <?= $link['title'] ?>
                </a>
            </li>
        <?php endforeach ?>

        <?php if ($pager->hasNext()) : ?>
            <li class="page-item">
                <a href="<?= $pager->getNext() ?>" hx-get="<?= $pager->getNext() ?>" aria-label="<?= lang('Pager.next') ?>" class="page-link">
                    <span aria-hidden="true"><?= lang('Pager.next') ?></span>
                </a>
            </li>
            <li class="page-item">
                <a href="<?= $pager->getLast() ?>" hx-get="<?= $pager->getLast() ?>" aria-label="<?= lang('Pager.last') ?>" class="page-link">
                    <span aria-hidden="true"><?= lang('Pager.last') ?></span>
                </a>
            </li>
        <?php endif ?>
    </ul>
</nav>
<?php endif ?>
