<header class="navbar navbar-light flex-md-nowrap p-0 shadow-sm">
    <a class="px-3 d-block fs-3 text-dark text-decoration-none col-md-3 col-lg-2 me-0" href="/<?= ADMIN_AREA ?>">
        <?= config('App')->siteName ?? 'bonfire' ?>
        <span class="fs-6 text-black-50"><?= BONFIRE_VERSION ?></span>
    </a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Search Form -->
    <input class="form-control form-control bg-light w-100" type="text" placeholder="Search" aria-label="Search">

    <!-- User Meni -->
    <div class="dropdown text-end mx-4">
        <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
<!--            <img src="https://github.com/mdo.png" alt="mdo" class="rounded-circle" width="32" height="32">-->
            <?= auth()->user()->renderAvatar(32) ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="dropdownUser1" style="">
            <li><a class="dropdown-item" href="/<?= ADMIN_AREA ?>/users/<?= auth()->id() ?>">My Account</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= route_to('logout') ?>">Sign out</a></li>
        </ul>
    </div>
</header>
