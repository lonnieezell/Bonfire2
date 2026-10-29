<?php
/**
 * The offcanvas listing every available widget, by module, to add in edit
 * mode. Placed widgets are marked Added and can't be added again. It has
 * no backdrop and the dashboard makes room for it, so an entry can be
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
    <div class="bf-widget-palette-controls">
        <label class="bf-widget-palette-target">
            <?= lang('Bonfire.addTo') ?>
            <select class="form-select form-select-sm" x-model.number="target">
                <template x-for="(name, index) in sectionNames" :key="index">
                    <option :value="index" x-text="name"></option>
                </template>
            </select>
        </label>
        <div class="bf-widget-palette-search">
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" class="form-control form-control-sm" x-model="search" placeholder="<?= esc(lang('Bonfire.searchWidgets'), 'attr') ?>" aria-label="<?= esc(lang('Bonfire.searchWidgets'), 'attr') ?>">
        </div>
        <p class="bf-widget-palette-hint"><?= lang('Bonfire.widgetLibraryHint') ?></p>
    </div>
    <div class="offcanvas-body">
		<?php foreach ($groups as $group => $widgets) : ?>
            <div class="bf-palette-group" data-palette-group="<?= esc($group, 'attr') ?>" x-show="groupMatches($el)">
                <h3><?= esc($group) ?></h3>
                <ul class="list-unstyled" data-palette-list>
					<?php foreach ($widgets as $widget) : ?>
						<?php $size = $widget->sizes()->default ?>
                        <li class="bf-palette-entry bf-span-<?= $size->columns() ?>" data-palette-widget="<?= esc($widget->id(), 'attr') ?>"<?= in_array($widget->id(), $placed, true) ? ' data-placed' : '' ?>
                            x-show="matches($el)" data-search="<?= esc(mb_strtolower($widget->title() . ' ' . $widget->description()), 'attr') ?>">
                            <span class="bf-palette-entry-icon"><i class="<?= esc($widget->icon(), 'attr') ?>" aria-hidden="true"></i></span>
                            <span class="bf-palette-entry-text">
                                <span><strong><?= esc($widget->title()) ?></strong> <small class="bf-palette-entry-size" title="<?= esc(lang('Bonfire.size' . $size->name), 'attr') ?>"><?= lang('Bonfire.sizeLabels.' . $size->name) ?></small></span>
                                <small><?= esc($widget->description()) ?></small>
                            </span>
                            <span class="bf-palette-entry-added"><i class="fas fa-check" aria-hidden="true"></i> <?= lang('Bonfire.added') ?></span>
                            <button type="button" class="btn btn-sm btn-outline-secondary bf-palette-entry-add" @click="addWidget($el)"
                                aria-label="<?= esc(lang('Bonfire.addNamed', [$widget->title()]), 'attr') ?>"><?= lang('Bonfire.add') ?></button>
                            <template data-placeholder>
								<?= $this->setData([
								    'widget'    => $widget,
								    'placement' => new Bonfire\Dashboard\Placement($widget->id(), $size),
								    'html'      => $this->setData(['widget' => $widget])->include('Bonfire\Dashboard\Views\_placeholder'),
								])->include('Bonfire\Dashboard\Views\_tile') ?>
                            </template>
                        </li>
					<?php endforeach ?>
                </ul>
            </div>
		<?php endforeach ?>

        <p class="bf-widget-palette-empty" x-show="noMatches()" x-cloak><?= lang('Bonfire.noWidgetsMatch') ?></p>
    </div>
</div>
