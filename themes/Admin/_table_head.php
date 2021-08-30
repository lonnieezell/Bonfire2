<thead>
<?php if(isset($headers) && count($headers)) : ?>
    <tr>
        <?php if(isset($showSelectAll)) : ?>
            <th style="width: 3em">
                <input type="checkbox" class="form-check" id="select-all">
            </th>
        <?php endif ?>
    <?php foreach($headers as $column => $title) : ?>
        <th><?= strtoupper($title) ?></th>
    <?php endforeach ?>
        <th></th>
    </tr>
<?php endif ?>
</thead>
