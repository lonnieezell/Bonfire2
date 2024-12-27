<form action="<?= site_url(ADMIN_AREA . '/users/delete-batch') ?>" method="post">
    <?= csrf_field() ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <?= $this->include('_table_head') ?>
            <tbody>
            <?php if (isset($users) && count($users)) : ?>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <?php if (auth()->user()->can('users.delete')) : ?>
                            <td>
                                <input type="checkbox" name="selects[<?= $user->id ?>]" class="form-check">
                            </td>
                        <?php endif ?>
                        <?= view('Bonfire\Users\Views\_row_info', ['user' => $user]) ?>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>

    <?php if (auth()->user()->can('users.delete')) : ?>
        <input type="submit" value="Delete Selected" class="btn btn-sm btn-outline-danger" />
    <?php endif ?>
</form>

<div class="text-center">
    <?= $pager->links('default', 'bonfire_full_hx') ?>
</div>