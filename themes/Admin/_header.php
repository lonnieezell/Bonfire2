<?php
// Breadcrumb: the sidebar's active trail, plus the page's own `crumb`
// section (and optional `crumbParent`) for pages below a menu item.
$crumbs     = service('menus')->menu('sidebar')->activeTrail();
$pageCrumb  = trim($this->renderSection('crumb'));
$pageParent = trim($this->renderSection('crumbParent'));

if ($pageCrumb !== '') {
    $crumbs = [$pageParent ?: end($crumbs), $pageCrumb];
}

$current = array_pop($crumbs) ?? lang('Bonfire.dashboard');
$crumbs  = array_filter(array_slice($crumbs, -1));
?>
<header class="bf-header">
    <nav class="bf-crumbs" aria-label="<?= lang('Bonfire.breadcrumb') ?>">
        <ol>
            <?php foreach ($crumbs as $crumb) : ?>
                <li><?= $crumb ?></li>
            <?php endforeach ?>
            <li aria-current="page"><?= $current ?></li>
        </ol>
    </nav>

    <form action="<?= url_to('search') ?>" method="post" class="bf-search" role="search">
        <?= csrf_field() ?>
        <i class="fas fa-search" aria-hidden="true"></i>
        <input type="text" name="search_term" placeholder="<?= lang('Bonfire.search') ?>" aria-label="<?= lang('Bonfire.search') ?>"
            value="<?= esc(old('search_term', $searchTerm ?? '')) ?>">
        <kbd class="bf-kbd" aria-hidden="true">⌘K</kbd>
    </form>

    <button type="button" class="bf-icon-btn" onclick="bfTheme.toggle()" title="<?= lang('Bonfire.toggleTheme') ?>" aria-label="<?= lang('Bonfire.toggleTheme') ?>">
        <i class="fas fa-moon bf-when-light" aria-hidden="true"></i>
        <i class="fas fa-sun bf-when-dark" aria-hidden="true"></i>
    </button>

    <?php if (auth()->user()) : ?>
        <div class="dropdown">
            <button type="button" class="bf-avatar-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= lang('Bonfire.userMenu') ?>">
                <?= auth()->user()->renderAvatar(28) ?>
                <i class="fas fa-angle-down" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end bf-user-menu" id="user-menu">
                <div class="bf-user-menu-who">
                    <div class="fw-semibold"><?= esc(auth()->user()->displayName()) ?></div>
                    <div class="bf-muted"><?= esc(auth()->user()->email) ?></div>
                </div>
                <a class="dropdown-item" href="<?= site_url(ADMIN_AREA . '/users/' . auth()->id()) ?>">
                    <i class="fas fa-user" aria-hidden="true"></i><?= lang('Bonfire.myAccount') ?>
                </a>
                <button type="button" class="dropdown-item" onclick="bfTheme.toggle()">
                    <i class="fas fa-moon bf-when-light" aria-hidden="true"></i>
                    <i class="fas fa-sun bf-when-dark" aria-hidden="true"></i>
                    <?= lang('Bonfire.toggleTheme') ?>
                </button>
                <a class="dropdown-item" href="<?= site_url('logout') ?>">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i><?= lang('Bonfire.signOut') ?>
                </a>
            </div>
        </div>
    <?php endif ?>
</header>
