<div class="table-responsive">
    <table class="table table-hover">
        <?= $this->setData(['headers' => [
            'email'       => lang('Bonfire.email'),
            'username'    => lang('Bonfire.username'),
            'groups'      => lang('Bonfire.groups'),
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
