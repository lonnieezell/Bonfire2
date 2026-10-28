<?php
$hasUsers   = isset($users) && count($users) > 0;
$actionList = service('actions')->for('users')->showing($users ?? []);
$selectable = $actionList->selectable(auth()->user());
?>
<form method="post">
    <?= csrf_field() ?>
    <?php if ($hasUsers) : ?>
        <div class="table-responsive">
            <table class="table table-hover bf-table">
                <?= $this->setData(['showSelectAll' => $selectable])->include('_table_head') ?>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <?php if ($selectable) : ?>
                                <td class="bf-select-cell">
                                    <input type="checkbox" name="selects[]" value="<?= $user->id ?>" class="form-check-input" aria-label="<?= lang('Users.selectUser', [esc($user->username)]) ?>"
                                        <?php if (! $actionList->selectable(auth()->user(), $user)) : ?>
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

    <?php if ($selectable) : ?>
        <x-selection-bar>
            <x-bulk-actions list="users" />
        </x-selection-bar>
    <?php endif ?>
</form>

<?php if ($hasUsers) : ?>
    <?= $pager->links('default', 'bonfire_list_hx') ?>
<?php endif ?>
