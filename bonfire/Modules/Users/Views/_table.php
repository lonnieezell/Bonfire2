<table class="table table-hover">
    <?= $this->include('_table_head') ?>
    <tbody>
    <?php if (isset($users) && count($users)) : ?>
        <?php foreach ($users as $user) : ?>
            <tr>
                <td>
                    <input type="checkbox" name="selects[]" class="form-check">
                </td>
                <?= view('Bonfire\Modules\Users\Views\_row_info', ['user' => $user]) ?>
            </tr>
        <?php endforeach ?>
    <?php endif ?>
    </tbody>
</table>

<div class="text-center">
    <?= $pager->links() ?>
</div>
