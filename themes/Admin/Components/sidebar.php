<?php
$siteName    = setting('Site.siteName') ?? 'bonfire';
$onDashboard = url_is(ADMIN_AREA);
$activeItem  = isset($menu) ? $menu->activeItem() : null;
?>
<a class="bf-brand" href="<?= site_url(ADMIN_AREA) ?>">
    <span class="bf-stamp"><?= esc(mb_substr($siteName, 0, 1)) ?></span>
    <span class="bf-label"><?= esc($siteName) ?></span>
</a>

<nav class="bf-nav">
    <a class="bf-nav-link <?= $onDashboard ? 'active' : '' ?>" href="<?= site_url(ADMIN_AREA) ?>" title="<?= lang('Bonfire.dashboard') ?>"
        <?= $onDashboard ? 'aria-current="page"' : '' ?>>
        <i class="fas fa-home"></i>
        <span class="bf-label"><?= lang('Bonfire.dashboard') ?></span>
    </a>

    <?php if (isset($menu)) : ?>
        <?php foreach ($menu->items() as $collection) : ?>
            <?php if (! $collection instanceof Bonfire\Menus\MenuCollection) : ?>
                <?php $item = $collection ?>
                <?php if ($item->userCanSee()) : ?>
                    <?php $active = $item === $activeItem ?>
                    <a class="bf-nav-link <?= $active ? 'active' : '' ?>" href="<?= site_url($item->url) ?>" title="<?= esc($item->altText ?? strip_tags($item->title), 'attr') ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                        <?= $item->icon ?>
                        <span class="bf-label"><?= $item->title ?></span>
                    </a>
                <?php endif ?>
                <?php continue ?>
            <?php endif ?>

            <?php if (! $collection->hasVisibleItems()) {
                continue;
            } ?>

            <?php if ($collection->hasPageNav()) : ?>
                <?php $active = $collection->isActive() || in_array($activeItem, $collection->items(), true) ?>
                <a class="bf-nav-link <?= $active ? 'active' : '' ?>" href="<?= site_url($collection->firstVisibleItem()->url) ?>" title="<?= esc(strip_tags($collection->title), 'attr') ?>">
                    <?= $collection->icon ?>
                    <span class="bf-label"><?= $collection->title ?></span>
                </a>
            <?php elseif ($collection->isCollapsible()) : ?>
                <?php $open = $collection->isActive() || in_array($activeItem, $collection->items(), true) ?>
                <div class="bf-nav-group <?= $open ? 'open' : '' ?>" x-data="{ open: <?= $open ? 'true' : 'false' ?> }" :class="{ open }">
                    <button type="button" class="bf-nav-link <?= $open ? 'active' : '' ?>" @click="open = ! open" :aria-expanded="open" title="<?= esc(strip_tags($collection->title), 'attr') ?>">
                        <?= $collection->icon ?>
                        <span class="bf-label"><?= $collection->title ?></span>
                        <i class="fas fa-angle-down bf-caret"></i>
                    </button>
                    <div class="bf-subnav">
                        <div class="bf-subnav-title"><?= $collection->title ?></div>
                        <?php foreach ($collection->items() as $item) : ?>
                            <?php if ($item->userCanSee()) : ?>
                                <?php $active = $item === $activeItem ?>
                                <a class="bf-subnav-link <?= $active ? 'active' : '' ?>" href="<?= site_url($item->url) ?>"<?= $item->altText !== null ? ' title="' . esc($item->altText, 'attr') . '"' : '' ?> <?= $active ? 'aria-current="page"' : '' ?>>
                                    <?= $item->title ?>
                                </a>
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php else : ?>
                <?php foreach ($collection->items() as $item) : ?>
                    <?php if ($item->userCanSee()) : ?>
                        <?php $active = $item === $activeItem ?>
                        <a class="bf-nav-link <?= $active ? 'active' : '' ?>" href="<?= site_url($item->url) ?>" title="<?= esc($item->altText ?? strip_tags($item->title), 'attr') ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                            <?= $item->icon ?>
                            <span class="bf-label"><?= $item->title ?></span>
                        </a>
                    <?php endif ?>
                <?php endforeach ?>
            <?php endif ?>
        <?php endforeach ?>
    <?php endif ?>
</nav>
