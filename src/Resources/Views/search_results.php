<?php

/**
 * The records of a resource in the admin's global search.
 *
 * @var list<array{label: string, url: string}> $rows
 */
?>
<?php if ($rows === []) : ?>
    <p class="text-muted"><?= lang('Bonfire.nothingFound') ?></p>
<?php else : ?>
    <ul class="list-unstyled">
        <?php foreach ($rows as $row) : ?>
            <li data-search-result><a href="<?= esc($row['url'], 'attr') ?>"><?= esc($row['label']) ?></a></li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
