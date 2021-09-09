<table class="table table-hover">
    <?= $this->include('_table_head') ?>
    <tbody>
    <?php if (isset($users) && count($users)) : ?>
        <?php foreach($users as $user) : ?>
            <tr>
                <td>
                    <input type="checkbox" name="selects[]" class="form-check">
                </td>
                <td><a href="<?= $user->adminLink() ?>"><?= esc($user->email) ?></a></td>
                <td><a href="<?= $user->adminLink() ?>"><?= esc($user->username) ?></a></td>
                <td></td>
                <td><?= $user->lastLogin() !== null ? $user->lastLogin()->date->humanize() : '' ?></td>
                <td class="d-flex justify-content-end">
                    <!-- Action Menu -->
                    <div class="dropdown">
                        <button class="btn btn-default btn-sm dropdown-toggle btn-3-dots" type="button"  data-bs-toggle="dropdown" aria-expanded="false"></button>
                        <ul class="dropdown-menu">
                            <li><a href="<?= $user->adminLink() ?>" class="dropdown-item"><?= lang('Bonfire.edit') ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="<?= $user->adminLink('delete') ?>" class="dropdown-item"
                                   onclick="return confirm(<?= lang('Bonfire.deleteResource', ['user']) ?>)">
                                    <?= lang('Bonfire.delete') ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php endforeach ?>
    <?php endif ?>
    </tbody>
</table>

<div class="text-center">
    <?= $pager->links() ?>
</div>
