<?php
/**
 * A placed widget: its edit-mode handle and menu, and its content.
 *
 * @var Bonfire\Dashboard\DashboardWidget $widget
 * @var Bonfire\Dashboard\Placement       $placement
 * @var string                            $html    The widget's rendered content
 */
?>
<div class="bf-widget bf-span-<?= $placement->size->columns() ?>" data-widget-id="<?= esc($placement->id, 'attr') ?>" data-size="<?= $placement->size->value ?>" data-title="<?= esc($widget->title(), 'attr') ?>">
    <div class="bf-widget-tools" x-show="editing" x-cloak>
        <button type="button" class="bf-widget-handle" aria-label="<?= esc(lang('Bonfire.dragWidget', [$widget->title()]), 'attr') ?>">
            <i class="fas fa-grip-vertical" aria-hidden="true"></i>
        </button>
        <button type="button" class="bf-widget-tool" @click="removeWidget($el)" aria-label="<?= esc(lang('Bonfire.removeWidget', [$widget->title()]), 'attr') ?>">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
        <div class="dropdown">
            <button type="button" class="bf-widget-tool" data-bs-toggle="dropdown" aria-expanded="false"
                aria-label="<?= esc(lang('Bonfire.widgetMenu', [$widget->title()]), 'attr') ?>">
                <i class="fas fa-ellipsis" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header"><?= lang('Bonfire.widgetSize') ?></h6></li>
				<?php foreach ($widget->sizes()->presets() as $size) : ?>
                    <li>
                        <button type="button" class="dropdown-item" data-size-option="<?= $size->value ?>" data-columns="<?= $size->columns() ?>" @click="resize($el)">
							<?= lang('Bonfire.size' . $size->name) ?>
                        </button>
                    </li>
				<?php endforeach ?>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item" @click="move($el, -1)" :disabled="(revision, ! canMove($el, -1))"><?= lang('Bonfire.moveEarlier') ?></button></li>
                <li><button type="button" class="dropdown-item" @click="move($el, 1)" :disabled="(revision, ! canMove($el, 1))"><?= lang('Bonfire.moveLater') ?></button></li>
                <li><h6 class="dropdown-header"><?= lang('Bonfire.moveToSection') ?></h6></li>
                <template x-for="(name, index) in sectionNames" :key="index">
                    <li><button type="button" class="dropdown-item" @click="moveTo($el, index)" :disabled="(revision, sectionIndex($el) === index)" x-text="name"></button></li>
                </template>
            </ul>
        </div>
    </div>
    <div class="bf-widget-body" :inert="editing">
		<?= $html ?>
    </div>
</div>
