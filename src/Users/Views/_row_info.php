<?php [$statusTone, $statusLabel] = $user->status() ?>
<td>
    <div class="bf-user-cell">
        <?= $user->renderAvatar(30) ?>
        <div class="bf-user-cell-text">
            <a href="<?= $user->adminLink() ?>" class="bf-user-cell-name"><?= esc($user->displayName()) ?></a>
            <div class="bf-user-cell-email"><?= esc($user->email) ?></div>
        </div>
    </div>
</td>
<td class="bf-mono"><?= esc($user->username) ?></td>
<td>
    <div class="bf-pills">
        <?php foreach ($user->groupTitles() as $title) : ?>
            <span class="bf-pill"><?= esc($title) ?></span>
        <?php endforeach ?>
    </div>
</td>
<td><x-status tone="<?= $statusTone ?>" label="<?= esc($statusLabel) ?>" /></td>
<td class="bf-muted"><?= $user->last_active?->humanize() ?? lang('Users.never') ?></td>
<td class="text-end">
    <x-row-actions list="users" id="<?= $user->id ?>">
        <?php if (auth()->user()->can('users.edit')) : ?>
            <li><a href="<?= $user->adminLink() ?>" class="dropdown-item"><i class="fas fa-pen" aria-hidden="true"></i> <?= lang('Bonfire.edit') ?></a></li>
        <?php endif ?>
    </x-row-actions>
</td>
