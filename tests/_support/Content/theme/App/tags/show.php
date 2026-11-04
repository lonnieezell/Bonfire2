<h1>Tagged <?= esc($term->title) ?> in <?= esc($taxonomy->title) ?></h1>
<ul>
<?php foreach ($entries as $entry) : ?>
    <li><?= esc($entry->title) ?></li>
<?php endforeach ?>
</ul>
