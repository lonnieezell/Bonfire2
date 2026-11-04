<?php
/**
 * A sample for the navigation menu "main", made with Bootstrap's nav. Include it in a layout with
 * <?= $this->include('_navigation') ?>, and copy it to change the handle or the markup.
 * See docs/content/navigation.md.
 */
$items = navigation('main');
$list  = static function (array $items, bool $nested) use (&$list): void { ?>
    <ul class="<?= $nested ? 'list-unstyled ps-3' : 'nav' ?>">
        <?php foreach ($items as $item) : ?>
            <li class="nav-item">
                <?php if ($item['url'] === null) : ?>
                    <span class="nav-link disabled"><?= esc($item['title']) ?></span>
                <?php else : ?>
                    <a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= esc($item['url'], 'attr') ?>"<?= $item['active'] ? ' aria-current="page"' : '' ?>><?= esc($item['title']) ?></a>
                <?php endif ?>
                <?php if ($item['children'] !== []) {
                    $list($item['children'], true);
                } ?>
            </li>
        <?php endforeach ?>
    </ul>
<?php };
if ($items !== []) : ?>
    <nav aria-label="Main">
        <?php $list($items, false) ?>
    </nav>
<?php endif ?>
