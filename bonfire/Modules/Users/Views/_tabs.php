<ul class="nav nav-tabs nav-fill" style="margin-bottom: -2px;">
    <li class="nav-item">
        <a class="nav-link <?php if($tab === 'details') : ?> active <?php endif ?>"
           href="<?= isset($user) ? $user->adminLink() : '#' ?>">
            User Details
        </a>
    </li>
    <?php if(isset($user) && $user !== null) : ?>
        <?php if(auth()->user()->can('users.edit')) : ?>
            <li class="nav-item">
                <a class="nav-link <?php if($tab === 'permissions') : ?> active <?php endif ?>"
                   href="<?= $user->adminLink('permissions') ?>">
                    Permissions
                </a>
            </li>
        <?php endif ?>
    <?php endif ?>
    <li class="nav-item">
        <?php if(isset($user) && $user !== null) : ?>
            <a class="nav-link <?php if($tab === 'security') : ?> active <?php endif ?>"
               href="<?= $user->adminLink('security') ?>">
                Security
            </a>
        <?php endif ?>
    </li>
</ul>
