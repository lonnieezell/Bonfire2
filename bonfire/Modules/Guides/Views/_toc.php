<?php use Bonfire\Modules\Guides\Libraries\GuideCollection; ?>

<ul class="list-unstyled px-4">
<?php foreach($pages as $folder => $page) : ?>
    <?php if(is_string($page) && $page !== 'index.md') : ?>
    <li>
        <a href="/<?= ADMIN_AREA ?>/guides/<?= $alias ?>/<?= $page ?>">
            <?= esc(GuideCollection::formatPage($page)) ?>
        </a>
    </li>
    <?php endif ?>

    <?php if (is_array($page) && count($page)) : ?>
        </ul>

        <?= load_recursive_guide($alias, $page, $folder) ?>

    <?php endif ?>
<?php endforeach ?>
</ul>
