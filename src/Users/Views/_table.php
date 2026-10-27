<form action="<?= site_url(ADMIN_AREA . '/users/delete-batch') ?>" method="post">
    <?= csrf_field() ?>
    <?php if (isset($users) && count($users)) : ?>
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

<?php if (isset($users) && count($users)) : ?>
    <div class="bf-list-footer" hx-target="#content-list" hx-include=".bf-toolbar-form, .filters form">
        <span><?= lang('Users.showingOf', [count($users), $pager->getTotal()]) ?></span>
        <?php if ($pager->getPageCount() > 1) : ?>
            <?php
            $page     = $pager->getCurrentPage();
            $previous = $page > 1 ? $pager->getPageURI($page - 1) : null;
            $next     = $page < $pager->getPageCount() ? $pager->getPageURI($page + 1) : null;
            ?>
            <div class="bf-list-pager">
                <?php if ($previous !== null) : ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= $previous ?>" hx-get="<?= $previous ?>"><?= lang('Pager.previous') ?></a>
                <?php else : ?>
                    <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true"><?= lang('Pager.previous') ?></span>
                <?php endif ?>
                <?php if ($next !== null) : ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= $next ?>" hx-get="<?= $next ?>"><?= lang('Pager.next') ?></a>
                <?php else : ?>
                    <span class="btn btn-outline-secondary btn-sm disabled" aria-disabled="true"><?= lang('Pager.next') ?></span>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>
<?php endif ?>
