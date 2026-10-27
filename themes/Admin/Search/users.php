<div class="table-responsive">
    <table class="table table-hover bf-table">
        <?= $this->setData(['headers' => [
            'user'        => lang('Users.headers.user'),
            'username'    => lang('Bonfire.username'),
            'groups'      => lang('Bonfire.groups'),
            'status'      => lang('Users.headers.status'),
            'last_active' => lang('Bonfire.lastActive'),
        ]])->include('_table_head') ?>
        <tbody>
        <?php foreach ($rows as $user) : ?>
            <tr>
                <?= view('Bonfire\Users\Views\_row_info', ['user' => $user]) ?>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
