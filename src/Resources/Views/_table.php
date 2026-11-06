<?php

use CodeIgniter\Pager\Pager;

/**
 * The table and pager of a resource's list, which is all that an htmx refresh loads.
 *
 * @var list<Bonfire\Resources\Column|Bonfire\Fields\Field>                  $columns
 * @var list<array{record: mixed, cells: list<string>, editUrl: string|null}> $rows     editUrl is where the first cell links to
 * @var Pager                                           $pager
 * @var string                                          $sort     the handle of the sorted column, or ''
 * @var string                                          $dir      asc or desc
 * @var array<string, string>                           $sortUrls the link of each sortable column, by handle
 * @var bool                                            $narrowed whether a search or a filter is in use
 */
?>
<?php if ($rows === []) : ?>
    <?php if ($narrowed) : ?>
        <x-empty-state title="<?= esc(lang('Resources.noMatches'), 'attr') ?>" hint="<?= esc(lang('Resources.noMatchesHint'), 'attr') ?>" />
    <?php else : ?>
        <x-empty-state title="<?= esc(lang('Resources.empty'), 'attr') ?>" hint="<?= esc(lang('Resources.emptyHint'), 'attr') ?>" />
    <?php endif ?>
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
                            <td data-cell="<?= esc($column->handle, 'attr') ?>"><?= $i === 0 && $row['editUrl'] !== null ? '<a href="' . esc($row['editUrl'], 'attr') . '">' . esc($row['cells'][$i]) . '</a>' : esc($row['cells'][$i]) ?></td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <?= $pager->links('default', 'bonfire_list_hx') ?>
<?php endif ?>
