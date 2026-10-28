<?php
/**
 * The offcanvas listing every available widget, grouped, to add in edit
 * mode. Placed widgets are listed hidden, so removing one shows it again.
 * It has no backdrop and leaves the page scrollable, so an entry can be
 * dragged onto any section.
 *
 * @var array<string, list<Bonfire\Dashboard\DashboardWidget>> $groups
 * @var list<string>                                              $placed
 */
?>
<div class="offcanvas offcanvas-end bf-widget-palette" tabindex="-1" data-bs-backdrop="false" data-bs-scroll="true" id="bf-widget-palette" aria-labelledby="bf-widget-palette-title">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="bf-widget-palette-title"><?= lang('Bonfire.addWidgets') ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?= esc(lang('Actions.cancel'), 'attr') ?>"></button>
    </div>
    <div class="offcanvas-body">
        <label class="form-label" for="bf-palette-target"><?= lang('Bonfire.addTo') ?></label>
        <select class="form-select mb-3" id="bf-palette-target" x-model.number="target">
            <template x-for="(name, index) in sectionNames" :key="index">
                <option :value="index" x-text="name"></option>
            </template>
        </select>
        <input type="search" class="form-control mb-3" x-model="search" placeholder="<?= esc(lang('Bonfire.searchWidgets'), 'attr') ?>" aria-label="<?= esc(lang('Bonfire.searchWidgets'), 'attr') ?>">

		<?php foreach ($groups as $group => $widgets) : ?>
            <div class="bf-palette-group" data-palette-group="<?= esc($group, 'attr') ?>">
                <h3 class="h6"><?= lang('Bonfire.group' . ucfirst($group)) ?></h3>
                <ul class="list-unstyled" data-palette-list>
					<?php foreach ($widgets as $widget) : ?>
                        <li class="bf-palette-entry" data-palette-widget="<?= esc($widget->id(), 'attr') ?>"<?= in_array($widget->id(), $placed, true) ? ' hidden' : '' ?>
                            x-show="matches($el)" data-search="<?= esc(mb_strtolower($widget->title() . ' ' . $widget->description()), 'attr') ?>">
                            <i class="<?= esc($widget->icon(), 'attr') ?>" aria-hidden="true"></i>
                            <span class="bf-palette-entry-text">
                                <strong><?= esc($widget->title()) ?></strong>
                                <small><?= esc($widget->description()) ?></small>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="addWidget($el)"
                                aria-label="<?= esc(lang('Bonfire.addNamed', [$widget->title()]), 'attr') ?>"><?= lang('Bonfire.addWidget') ?></button>
                            <template data-placeholder>
								<?= $this->setData([
								    'widget'    => $widget,
								    'placement' => new Bonfire\Dashboard\Placement($widget->id(), $widget->sizes()->default),
								    'html'      => $this->setData(['widget' => $widget])->include('Bonfire\Dashboard\Views\_placeholder'),
								])->include('Bonfire\Dashboard\Views\_tile') ?>
                            </template>
                        </li>
					<?php endforeach ?>
                </ul>
            </div>
		<?php endforeach ?>

        <p class="text-muted" x-show="allPlaced" x-cloak><?= lang('Bonfire.noWidgetsLeft') ?></p>
    </div>
</div>
