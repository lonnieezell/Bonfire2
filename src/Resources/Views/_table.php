<?php

use CodeIgniter\Pager\Pager;

/**
 * The table and pager of a resource's list, which is all that an htmx refresh loads.
 *
 * @var list<Bonfire\Fields\Field>                      $columns
 * @var list<array{record: mixed, cells: list<string>}> $rows
 * @var Pager                                           $pager
 * @var string                                          $sort     the handle of the sorted column, or ''
 * @var string                                          $dir      asc or desc
 * @var array<string, string>                           $sortUrls the link of each sortable column, by handle
 */
?>
<?php if ($rows === []) : ?>
    <x-empty-state title="<?= esc(lang('Resources.empty'), 'attr') ?>" hint="<?= esc(lang('Resources.emptyHint'), 'attr') ?>" />
<?php else : ?>
    <div class="table-responsive">
        <table class="table table-hover bf-table">
            <thead>
                <tr>
                    <?php foreach ($columns as $column) : ?>
                        <th data-column="<?= esc($column->handle, 'attr') ?>"<?php if ($column->handle === $sort) : ?> aria-sort="<?= $dir === 'desc' ? 'descending' : 'ascending' ?>"<?php endif ?>>
                            <?php if (isset($sortUrls[$column->handle])) : ?>
                                <a href="<?= esc($sortUrls[$column->handle], 'attr') ?>" hx-get="<?= esc($sortUrls[$column->handle], 'attr') ?>">
                                    <?= esc($column->label) ?>
                                    <?php if ($column->handle === $sort) : ?>
                                        <i class="fas fa-sort-<?= $dir === 'desc' ? 'down' : 'up' ?>" aria-hidden="true"></i>
                                    <?php endif ?>
                                </a>
                            <?php else : ?>
                                <?= esc($column->label) ?>
                            <?php endif ?>
                        </th>
                    <?php endforeach ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr data-row>
                        <?php foreach ($columns as $i => $column) : ?>
                            <td data-cell="<?= esc($column->handle, 'attr') ?>"><?= esc($row['cells'][$i]) ?></td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <?= $pager->links('default', 'bonfire_list_hx') ?>
<?php endif ?>
