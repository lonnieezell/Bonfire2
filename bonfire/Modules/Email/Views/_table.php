<form action="<?= site_url(ADMIN_AREA . '/content/delete-email'); ?>" method="post">
    <?= csrf_field() ?>

<table class="table table-hover">
    <?= $this->include('_table_head') ?>
    <tbody>
    <?php if (isset($queue) && count($queue)) : ?>
        <?php foreach($queue as $email) : ?>
            <tr>
                <td>
                    <input type="checkbox" name="checked[]" class="form-check" value="<?= $email['id']; ?>">
                </td>
                <?= view('Bonfire\Modules\Email\Views\_row_info', ['email' => $email]) ?>
            </tr>
        <?php endforeach ?>
    <?php endif ?>
    </tbody>
</table>

<div class="text-center">
    <?= $pager->links() ?>
</div>

<input type="submit" name="delete" id="delete-me" class="btn btn-danger" value="<?php echo lang('Email.delete_selected'); ?>" onclick="return confirm('<?= lang('Email.delete_selected_confirm'); ?>')" />

</form>
