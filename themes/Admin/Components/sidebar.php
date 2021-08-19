<div class="position-sticky pt-3 mt-5">
    <!-- Dashboard -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= url_is('/'.ADMIN_AREA) ? 'active' : '' ?>" href="/<?= ADMIN_AREA ?>"><i class="fas fa-home"></i> Dashboard</a>
        </li>
    </ul>

    <?php if(isset($menu)) : ?>
        <?php foreach($menu->collections() as $collection) : ?>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span><?= $collection->title ?></span>
            </h6>

            <ul class="nav flex-column mb-2">
            <?php foreach($collection->items() as $item) : ?>
                <li class="nav-item">
                    <a class="nav-link <?= url_is($item->url) ? 'active' : '' ?>" href="<?= $item->url ?>">
                        <?= $item->icon ?>
                        <?= $item->title ?>
                    </a>
                </li>
            <?php endforeach ?>
            </ul>
        <?php endforeach ?>
    <?php endif ?>
</div>
