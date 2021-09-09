<ul class="nav nav-tabs nav-fill" style="margin-bottom: -2px;">
    <li class="nav-item">
        <a class="nav-link <?php if($tab === 'details') : ?> active <?php endif ?>"
           href="<?= isset($user) ? $user->adminLink() : '#' ?>">
            User Details
        </a>
    </li>
    <li class="nav-item">
        <?php if(isset($user) && $user !== null) : ?>
            <a class="nav-link <?php if($tab === 'security') : ?> active <?php endif ?>"
               href="<?= $user->adminLink('security') ?>">
                Security
            </a>
        <?php endif ?>
    </li>
</ul>
