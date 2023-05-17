<table class="table">
    <thead>
        <tr>
            <th>Role</th>
            <th>Description</th>
            <th class="text-center"># Users</th>
        </tr>
    </thead>
    <tbody>
        <?php if (isset($groups) && count($groups)) : ?>
            <?php foreach ($groups as $alias => $group) : ?>
                <tr>
                    <td>
                        <a href="<?= site_url(ADMIN_AREA . '/settings/groups/' . $alias) ?>">
                            <?= esc($group['title']) ?>
                        </a>
                    </td>
                    <td><?= esc($group['description']) ?></td>
                    <td class="text-center"><?= esc(number_format($group['user_count'])) ?></td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>