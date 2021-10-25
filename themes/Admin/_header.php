<header class="navbar navbar-light flex-md-nowrap p-0 shadow-sm">
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Search Form -->
    <form action="<?= route_to('search') ?>" method="post" class="w-100">
        <?= csrf_field() ?>

        <input class="form-control form-control bg-light w-100" type="text" name="search_term" placeholder="Search" aria-label="Search"
            value="<?= old('search_term', $searchTerm ?? '') ?>">
    </form>

    <!-- User Menu -->
    <div class="dropdown text-end mx-4">
        <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <?= auth()->user()->renderAvatar(32) ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="dropdownUser1" style="">
            <li><a class="dropdown-item" href="/<?= ADMIN_AREA ?>/users/<?= auth()->id() ?>">My Account</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= route_to('logout') ?>">Sign out</a></li>
        </ul>
    </div>
</header>
