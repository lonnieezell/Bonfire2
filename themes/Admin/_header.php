<header class="navbar navbar-light flex-md-nowrap p-0 shadow-sm d-flex">
    <button class="navbar-toggler d-sm-none collapsed mx-2 border-none" type="button"
        @click="open = ! open"
    >
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Search Form -->
    <form action="<?= route_to('search') ?>" method="post" class="flex-grow-1">
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
