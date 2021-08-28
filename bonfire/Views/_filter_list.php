<section class="filters">
    <?php if(isset($filters) && count($filters)): ?>
        <?php foreach($filters as $key => $filter): ?>
            <h2><?= $filter['title'] ?></h2>

            <ul class="list-unstyled">
            <?php foreach ($filter['options'] as $value => $name): ?>
                <li>
                    <a class="filter-link" data-val="<?= $value ?>">
                        <?= $name ?>
                    </a>
                </li>
            <?php endforeach ?>
            </ul>
        <?php endforeach ?>
    <?php endif ?>
</section>
