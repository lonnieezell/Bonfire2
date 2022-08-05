<a class="px-3 d-block fs-3 text-light text-decoration-none me-0" href="/<?= ADMIN_AREA ?>">
    <div class="site-stamp rounded d-inline-flex align-content-center justify-content-center">
        <?= substr(setting('Site.siteName') ?? 'bonfire', 0, 1) ?>
    </div>
    <span class="site-name"><?= setting('Site.siteName') ?? 'bonfire' ?></span>
</a>
<div class="pt-3">

    <!-- Dashboard -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= url_is('/' . ADMIN_AREA) ? 'active' : '' ?>" href="/<?= ADMIN_AREA ?>" title="Dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>

    <?php if (isset($menu)) : ?>
        <?php foreach ($menu->collections() as $collection) : ?>

        <div>
            <ul class="nav flex-column px-0">
                <?php if ($collection->isCollapsible()) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $collection->isActive() ? 'active' : '' ?>" href="#">
                            <?= $collection->icon ?>
                            <span><?= $collection->title ?></span>
                        </a>
                    </li>
                    <ul class="nav subnav flex-column mb-2  <?= $collection->isActive() ? 'active' : 'flyout' ?>">
                        <li class="nav-item nav-title">
                            <a class="nav-link">
                                <?= $collection->title ?>
                            </a>
                        </li>
                <?php endif ?>


                <?php foreach ($collection->items() as $item) : ?>
                    <?php if ($item->userCanSee()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= url_is($item->url . '*') ? 'active' : '' ?>" href="<?= $item->url ?>">
                                <?php if (! $collection->isCollapsible()) : ?>
                                    <?= $item->icon ?>
                                <?php endif ?>
                                <span><?= $item->title ?></span>
                            </a>
                        </li>
                    <?php endif ?>
                <?php endforeach ?>
                <?php if ($collection->isCollapsible()) : ?>
                    </ul>
                <?php endif ?>
            </ul>
        </div>
        <?php endforeach ?>
    <?php endif ?>
</div>
