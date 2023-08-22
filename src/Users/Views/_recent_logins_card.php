<table class="table">
    <thead>
        <tr>
            <th>Name, IP</th>
            <th>Date</th>
            <th>OK?</th>
        </tr>
    </thead>
    <?php if (isset($logins) && count($logins)) : ?>
        <tbody>
            <?php foreach ($logins as $login) : ?>
                <tr>
                    <td><?= ($login->first_name ?? 'unidentified') . '&nbsp' . $login->last_name . '<br>' . $login->ip_address ?></td>
                    <td><?= app_date($login->date, true, true) ?></td>
                    <td>
                        <?php if ((int) $login->success === 1) : ?>
                            <span class="badge rounded-pill bg-success">Yes</span>
                        <?php else : ?>
                            <span class="badge rounded-pill bg-danger">No</span>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    <?php else : ?>
        <div class="alert alert-secondary">No recent login attempts.</div>
    <?php endif ?>
</table>