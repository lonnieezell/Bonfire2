<td><a href="<?= $user->adminLink() ?>"><?= esc($user->email) ?></a></td>
<td><a href="<?= $user->adminLink() ?>"><?= esc($user->username) ?></a></td>
<td><?= $user->groupsList() ?></td>
<td><?= $user->last_active !== null ? $user->last_active->humanize() : 'never' ?></td>
<td class="d-flex justify-content-end">
    <?php if (auth()->user()->can('users.edit') || auth()->user()->can('users.delete')): ?>
        <!-- Action Menu -->
        <div class="dropdown">
            <button class="btn btn-default btn-sm dropdown-toggle btn-3-dots" type="button"  data-bs-toggle="dropdown" aria-expanded="false"></button>
            <ul class="dropdown-menu">
                <?php if (auth()->user()->can('users.edit')) : ?>
                    <li><a href="<?= $user->adminLink() ?>" class="dropdown-item"><?= lang('Bonfire.edit') ?></a></li>
                <?php endif ?>
                <?php if (auth()->user()->can('users.delete')): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="<?= $user->adminLink('delete') ?>" class="dropdown-item"
                        onclick="return confirm(<?= lang('Bonfire.deleteResource', ['user']) ?>)">
                            <?= lang('Bonfire.delete') ?>
                        </a>
                    </li>
                <?php endif ?>
            </ul>
        </div>
    <?php endif ?>
</td>
