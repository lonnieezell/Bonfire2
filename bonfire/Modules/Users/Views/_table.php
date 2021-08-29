<table class="table table-hover">
    <?= $this->include('_table_head') ?>
    <tbody>
    <?php if (isset($users) && count($users)) : ?>
        <?php foreach($users as $user) : ?>
            <tr>
                <td>
                    <input type="checkbox" name="selects[]" class="form-check">
                </td>
                <td><?= $user->email ?></td>
                <td><?= $user->username ?></td>
                <td></td>
                <td><?= $user->lastLogin()->date->humanize() ?></td>
                <td class="d-flex justify-content-end">
                    <!-- Action Menu -->
                    <div class="dropdown">
                        <button class="btn btn-default btn-sm dropdown-toggle btn-3-dots" type="button"  data-bs-toggle="dropdown" aria-expanded="false"></button>
                        <ul class="dropdown-menu">
                            <li><button class="dropdown-item" type="button">Action</button></li>
                            <li><button class="dropdown-item" type="button">Another action</button></li>
                            <li><button class="dropdown-item" type="button">Something else here</button></li>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php endforeach ?>
    <?php endif ?>
    </tbody>
</table>

<div class="text-center">
    <?= $pager->links() ?>
</div>
