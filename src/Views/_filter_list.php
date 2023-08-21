<section class="filters">
    <?php if (isset($filters) && count($filters)): ?>
    <form action="<?= current_url() ?>" method="post"
          hx-post="<?= current_url() ?>"
          hx-trigger="change delay:400ms from:.filter-check"
          hx-target="<?= $target ?>"
    >
        <?= csrf_field() ?>

        <?php foreach ($filters as $key => $filter) : ?>
            <h2><?= $filter['title'] ?></h2>

            <ul class="list-unstyled">
            <?php if (isset($filter['type']) && $filter['type'] === 'radio'): ?>
                <?php foreach ($filter['options'] as $value => $name): ?>
                    <li class="form-check">
                        <input class="form-check-input filter-check" type="radio" name="filters[<?= $key ?>]" value="<?= $value ?>" id="<?= $key . ':' . $value ?>>" <?= $key === 'all' ?: ' checked' ?>>
                        <label class="form-check-label" for="<?= $key . ':' . $value ?>">
                            <?= $name ?>
                        </label>
                    </li>
                <?php endforeach ?>
            <?php else :  ?>
                <?php foreach ($filter['options'] as $value => $name) : ?>
                    <li class="form-check">
                        <input class="form-check-input filter-check" type="checkbox" name="filters[<?= $key ?>][<?= $value ?>]" value="<?= $value ?>" id="<?= $key . ':' . $value ?>>">
                        <label class="form-check-label" for="<?= $key . ':' . $value ?>">
                            <?= $name ?>
                        </label>
                    </li>
                <?php endforeach ?>
            <?php endif; ?>
            </ul>
        <?php endforeach ?>
    </form>
    <?php endif ?>
</section>