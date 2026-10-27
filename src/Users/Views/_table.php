<?php $hasUsers = isset($users) && count($users) > 0 ?>
<form action="<?= site_url(ADMIN_AREA . '/users/delete-batch') ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($hasUsers) : ?>
        <div class="table-responsive">
            <table class="table table-hover bf-table">
                <?= $this->include('_table_head') ?>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <?php if (auth()->user()->can('users.delete')) : ?>
                                <td class="bf-select-cell">
                                    <input type="checkbox" name="selects[<?= $user->id ?>]" class="form-check-input" aria-label="<?= lang('Users.selectUser', [esc($user->username)]) ?>"
                                        <?php if (! $access->canSelectForDeletion($user)) : ?>
                                            disabled
                                        <?php endif; ?>
                                    >
                                </td>
                            <?php endif; ?>
                            <?= view('Bonfire\Users\Views\_row_info', ['user' => $user]) ?>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <x-empty-state title="<?= lang('Users.noUsersMatch') ?>" hint="<?= lang('Users.noUsersHint') ?>" />
    <?php endif ?>

    <?php if (auth()->user()->can('users.delete')) : ?>
        <x-selection-bar>
            <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('<?= lang('Bonfire.deleteSelectedConfirm', [lang('Users.usersAccusative')]) ?>')">
                <i class="fas fa-trash" aria-hidden="true"></i> <?= lang('Bonfire.delete') ?>
            </button>
        </x-selection-bar>
    <?php endif ?>
</form>

<?php if ($hasUsers) : ?>
    <?= $pager->links('default', 'bonfire_list_hx') ?>
<?php endif ?>
