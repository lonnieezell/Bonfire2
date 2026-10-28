<?php $hasUsers = isset($users) && count($users) > 0 ?>
<form method="post">
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
                                    <input type="checkbox" name="selects[]" value="<?= $user->id ?>" class="form-check-input" aria-label="<?= lang('Users.selectUser', [esc($user->username)]) ?>"
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
        <x-empty-state title="<?= esc(lang('Users.noUsersMatch')) ?>" hint="<?= esc(lang('Users.noUsersHint')) ?>" />
    <?php endif ?>

    <?php if (auth()->user()->can('users.delete')) : ?>
        <x-selection-bar>
            <x-bulk-actions list="users" />
        </x-selection-bar>
    <?php endif ?>
</form>

<?php if ($hasUsers) : ?>
    <?= $pager->links('default', 'bonfire_list_hx') ?>
<?php endif ?>
