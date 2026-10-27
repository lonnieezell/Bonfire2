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
    <?php if (auth()->user()->can('users.edit') || auth()->user()->can('users.delete')): ?>
        <!-- Action Menu -->
        <div class="dropdown">
            <button class="bf-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= lang('Bonfire.actionsCol') ?>">
                <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end bf-row-menu">
                <?php if (auth()->user()->can('users.edit')) : ?>
                    <li><a href="<?= $user->adminLink() ?>" class="dropdown-item"><i class="fas fa-pen" aria-hidden="true"></i> <?= lang('Bonfire.edit') ?></a></li>
                <?php endif ?>
                <?php if (auth()->user()->can('users.delete')): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="<?= $user->adminLink('delete') ?>" class="dropdown-item text-danger"
                        onclick="return confirm('<?= lang('Bonfire.deleteResource', [lang('Users.userAccusative')]) ?>')">
                            <i class="fas fa-trash" aria-hidden="true"></i> <?= lang('Bonfire.delete') ?>
                        </a>
                    </li>
                <?php endif ?>
            </ul>
        </div>
    <?php endif ?>
</td>
