<table class="table table-hover">
    <?= $this->setData(['headers' => [
        'email'       => 'Email',
        'username'    => 'Username',
        'groups'      => 'Groups',
        'last_active' => 'Last Active',
    ]])->include('_table_head') ?>
    <tbody>
    <?php foreach ($rows as $user) : ?>
        <tr>
            <?= view('Bonfire\Modules\Users\Views\_row_info', ['user' => $user]) ?>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
