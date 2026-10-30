<?php
// The frame every settings page shares: the "Settings" head, a sub-nav of the
// settings pages the user may open (the sidebar's `settings` collection), and the page.
$menu   = service('menus')->menu('sidebar');
$active = $menu->activeItem();
?>
<x-page-head title="<?= lang('Bonfire.settingsModTitle') ?>" subtitle="<?= lang('Bonfire.settingsSubtitle') ?>" />
<div class="bf-settings">
    <nav class="bf-settings-nav" aria-label="<?= lang('Bonfire.settingsModTitle') ?>">
        <?php foreach ($menu->collection('settings')?->items() ?? [] as $item) : ?>
            <?php if (! $item->userCanSee()) {
                continue;
            } ?>
            <a class="bf-settings-nav-link<?= $item === $active ? ' active' : '' ?>" href="<?= esc($item->url()) ?>"<?= $item->altText() !== null ? ' title="' . esc($item->altText(), 'attr') . '"' : '' ?><?= $item === $active ? ' aria-current="page"' : '' ?>>
                <?= $item->icon ?>
                <?= esc($item->title()) ?>
            </a>
        <?php endforeach ?>
    </nav>
    <div class="bf-settings-body">
        <?= $slot ?>
    </div>
</div>
