<?php

use Bonfire\Widgets\WidgetOptions;

/**
 * @var string                       $tab  The alias of the current tab, '' for the widgets list
 * @var array<string, WidgetOptions> $tabs
 */
?>
<x-tabs>
    <?php foreach (['' => lang('Widgets.widgetsModTitle')] + array_map(static fn (WidgetOptions $options) => $options->title, $tabs) as $alias => $title) : ?>
        <li class="nav-item">
            <a class="nav-link<?= $tab === (string) $alias ? ' active' : '' ?>"
               href="<?= site_url(ADMIN_AREA . '/settings/widgets' . ($alias === '' ? '' : '/' . $alias)) ?>">
                <?= esc($title) ?>
            </a>
        </li>
    <?php endforeach ?>
</x-tabs>
