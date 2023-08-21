<header class="navbar navbar-light flex-md-nowrap p-0 shadow-sm d-flex">
<?php
/** disable button untill a more elegant solution is
 * found. The menu can be toggled using the bottom-left expand button anyway
 * <button class="navbar-toggler d-sm-none collapsed mx-2 border-none" type="button" @click="open = ! open">
 *     <span class="navbar-toggler-icon"></span>
 * </button>
 */
?>
    <!-- Search Form -->
    <form action="<?= url_to('search') ?>" method="post" class="flex-grow-1">
        <?= csrf_field() ?>

        <input class="form-control form-control bg-light w-100" type="text" name="search_term" placeholder="<?= lang('Bonfire.search') ?>" aria-label="<?= lang('Bonfire.search'); ?>"
            value="<?= old('search_term', $searchTerm ?? '') ?>">
    </form>

    <!-- User Menu -->
    <?php if (auth()->user()) :?>
        <div class="dropdown text-end mx-4">
            <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <?= auth()->user()->renderAvatar(32) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end text-small" aria-labelledby="dropdownUser1" style="">
                <li><a class="dropdown-item" href="<?= site_url(ADMIN_AREA . '/users/' . auth()->id()) ?>"><?= lang('Bonfire.myAccount') ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= site_url('logout') ?>"><?= lang('Bonfire.signOut') ?></a></li>
            </ul>
        </div>
    <?php endif ?>
</header>
