<?php
/*
 * A sample menu from navigation(). Include it from a view with $this->include('_navigation'), and copy it to change the markup.
 */

$renderItems = static function (array $items) use (&$renderItems): void { ?>
    <ul>
        <?php foreach ($items as $item) : ?>
            <li>
                <a href="<?= esc($item->url, 'attr') ?>"<?= $item->active ? ' aria-current="page"' : '' ?>><?= esc($item->title) ?></a>
                <?php if ($item->children !== []) {
                    $renderItems($item->children);
                } ?>
            </li>
        <?php endforeach ?>
    </ul>
<?php };

$renderItems(navigation('main'));
