<nav aria-label="<?= esc(lang('Tools.navigateFiles'), 'attr') ?>">
    <ul class="pagination">
        <?php if ($prev['link']) : ?>
        <li
            class="page-item">
            <a class="page-link"
                href="<?= url_to('view-log', $prev['link']) ?>">
                <i class="fas fa-angle-double-left"></i> <span class="d-none d-md-inline"><?= $prev['label'] ?></span>
            </a>
        </li>
        <?php endif; ?>
        <li
            class="page-item active">
            <a class="page-link" href="#">
                <?= $curr['label'] ?>
            </a>
        </li>
        <?php if ($next['link']) : ?>
        <li
            class="page-item">
            <a class="page-link"
                href="<?= url_to('view-log', $next['link']) ?>">
                <span class="d-none d-md-inline"><?= $next['label'] ?></span> <i class="fas fa-angle-double-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>