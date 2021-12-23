<a class="px-3 d-block fs-3 text-light text-decoration-none me-0" href="/<?= ADMIN_AREA ?>">
    <div class="site-stamp rounded d-inline-flex align-content-center justify-content-center">
        B
    </div>
    <span class="site-name"><?= setting('App.siteName') ?? 'bonfire' ?></span>
</a>
<div class="pt-3">

    <!-- Dashboard -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= url_is('/'.ADMIN_AREA) ? 'active' : '' ?>" href="/<?= ADMIN_AREA ?>" title="Dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>

    <?php if(isset($menu)) : ?>
        <?php foreach($menu->collections() as $collection) : ?>

        <div>
            <?php if($collection->isCollapsible()) : ?>
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1">
                    <span><?= $collection->title ?></span>
                </h6>
            <?php else : ?>
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1">
                    <span><?= $collection->title ?></span>
                </h6>
            <?php endif ?>

            <ul class="nav flex-column mb-2 px-0">
                <?php foreach($collection->items() as $item) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?= url_is($item->url.'*') ? 'active' : '' ?>" href="<?= $item->url ?>">
                            <?= $item->icon ?>
                            <span><?= $item->title ?></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
        <?php endforeach ?>
    <?php endif ?>
</div>
