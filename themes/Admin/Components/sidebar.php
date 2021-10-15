<a class="px-3 d-block fs-3 text-light text-decoration-none me-0" href="/<?= ADMIN_AREA ?>">
    <?= config('App')->siteName ?? 'bonfire' ?>
</a>
<div class="pt-3">

    <!-- Dashboard -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= url_is('/'.ADMIN_AREA) ? 'active' : '' ?>" href="/<?= ADMIN_AREA ?>"><i class="fas fa-home"></i> Dashboard</a>
        </li>
    </ul>

    <?php if(isset($menu)) : ?>
        <?php foreach($menu->collections() as $collection) : ?>

        <div x-data="{expanded: <?= ! $collection->isCollapsible() ? 'true' : 'false' ?>}">
            <?php if($collection->isCollapsible()) : ?>
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 collapsible"
                    @click="expanded = ! expanded">
                    <span><?= $collection->title ?></span>
                    <span>&plus;</span>
                </h6>

                <div class="px-3">
            <?php else : ?>
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1">
                    <span><?= $collection->title ?></span>
                </h6>
            <?php endif ?>

            <ul class="nav flex-column mb-2" x-show="expanded">
                <?php foreach($collection->items() as $item) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?= url_is($item->url.'*') ? 'active' : '' ?>" href="<?= $item->url ?>">
                            <?= $item->icon ?>
                            <?= $item->title ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>

            <?php if($collection->isCollapsible()) : ?>
                </div>
            <?php endif ?>
        </div>
        <?php endforeach ?>
    <?php endif ?>
</div>
