<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head>
    <a href="/<?= ADMIN_AREA ?>/users" class="back">&larr; Users</a>
    <h2>Edit User</h2>
</x-page-head>

<?= view('Bonfire\Modules\Users\Views\_tabs', ['tab' => 'security', 'user' => $user]) ?>

<x-admin-box>

    <fieldset>
        <legend>Recent Logins</legend>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Success?</th>
                </tr>
            </thead>
            <?php if (isset($logins) && count($logins)) : ?>
            <tbody>
                <?php foreach ($logins as $login) : ?>
                    <tr>
                        <td><?= $login->date->format('M j, Y h:ia T') ?? '' ?></td>
                        <td><?= $login->ip_address ?? '' ?></td>
                        <td><?= $login->user_agent ?? '' ?></td>
                        <td>
                            <?php if ($login->success) : ?>
                                <span class="badge rounded-pill bg-success">Success</span>
                            <?php else : ?>
                                <span class="badge rounded-pill bg-secondary">Failed</span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <?php else : ?>
                <div class="alert alert-secondary">No recent login attempts.</div>
            <?php endif ?>
        </table>
    </fieldset>


    <fieldset>
        <legend>Password Reset Attempts</legend>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Token</th>
                </tr>
            </thead>
            <?php if (isset($resetAttempts) && count($resetAttempts)) : ?>
            <tbody>
                <?php foreach ($resetAttempts as $attempt) : ?>
                    <tr>
                        <td><?= $attempt->created_at->format('M j, Y h:ia T') ?? '' ?></td>
                        <td><?= $attempt->ip_address ?? '' ?></td>
                        <td><?= $attempt->user_agent ?></td>
                        <td><?= $attempt->token ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <?php else : ?>
                <div class="alert alert-secondary">No recent password reset attempts.</div>
            <?php endif ?>
        </table>
    </fieldset>

</x-admin-box>

<?php $this->endSection() ?>
