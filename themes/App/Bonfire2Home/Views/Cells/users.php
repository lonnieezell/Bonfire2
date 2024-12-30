<?php /* the space in the next line before closing ` >` is needed as a workaround for bug #480 */ ?>
<x-sidebar-card title="Newest Users" >
    <ul class="list-unstyled">
        <?php foreach ($recentUsers as $user) : ?>
            <li class="d-flex align-items-center my-2">
                <img src="<?= $user->gravatarUrl ?>" alt="Gravatar" class="rounded-circle my-1 me-2">
                <span><?= $user->first_name ?> <?= $user->last_name ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</x-sidebar-card>