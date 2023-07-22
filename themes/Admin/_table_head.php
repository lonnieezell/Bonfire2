<thead>
<?php if (isset($headers) && count($headers)) : ?>
    <tr>
        <?php if (isset($showSelectAll) && auth()->user()->can('users.delete')) : ?>
            <th style="width: 3em">
                <input type="checkbox" class="form-check select-all">
            </th>
        <?php endif ?>
    <?php foreach ($headers as $column => $title) : ?>
        <th><?= mb_strtoupper($title) ?></th>
    <?php endforeach ?>
    <?php if(auth()->user()->can('users.edit') || auth()->user()->can('users.delete')): ?>
        <th></th>
    <?php endif ?>
    </tr>
<?php endif ?>
</thead>
