<?php foreach ($tabs as $tab) : ?>
    <?php if (empty($tab->permission) || auth()->user()->can($tab->permission)) : ?>
        <li class="nav-item">
            <a class="nav-link <?php if (url_is($tab->url)) : ?> active <?php endif ?>"
               href="<?= esc($tab->url, 'attr') ?>">
                <?= esc($tab->title) ?>
            </a>
        </li>
    <?php endif ?>
<?php endforeach ?>
