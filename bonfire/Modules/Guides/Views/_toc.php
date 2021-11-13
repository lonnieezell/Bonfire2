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
        <h3><?= esc(ucwords(trim(GuideCollection::formatPage($folder), ' /'))) ?></h3>

        <ul class="list-unstyled px-4">
        <?php foreach($page as $row) : ?>
            <li>
                <a href="/<?= ADMIN_AREA ?>/guides/<?= $alias ?>/<?= $folder ?>/<?= trim($row, ' /') ?>">
                    <?= esc(GuideCollection::formatPage($row)) ?>
                </a>
            </li>
        <?php endforeach ?>
        </ul>
    <?php endif ?>
<?php endforeach ?>
</ul>
