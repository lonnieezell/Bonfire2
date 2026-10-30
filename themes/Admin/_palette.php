<?php
// The ⌘K command palette: every sidebar page the user may see, plus a
// final row that submits the header's search form.
$paletteItems = [['label' => lang('Bonfire.dashboard'), 'hint' => '', 'url' => site_url(ADMIN_AREA)]];

foreach (service('menus')->menu('sidebar')->items() as $entry) {
    $collection = $entry instanceof Bonfire\Menus\MenuCollection ? $entry : null;

    foreach ($collection?->items() ?? [$entry] as $item) {
        if ($item->userCanSee()) {
            $paletteItems[] = [
                'label' => html_entity_decode(strip_tags($item->title)),
                'hint'  => html_entity_decode(strip_tags($collection->title ?? '')),
                'url'   => site_url($item->url),
            ];
        }
    }
}
?>
<script type="application/json" id="bf-palette-items"><?= json_encode($paletteItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<div class="bf-palette" x-data="bfPalette" x-show="open" x-cloak @keydown.escape.window="close()"
    data-search-label="<?= esc(lang('Bonfire.searchFor', ['%s']), 'attr') ?>"
    role="dialog" aria-modal="true" aria-label="<?= lang('Bonfire.commandPalette') ?>">
    <div class="bf-palette-backdrop" @click="close()"></div>
    <div class="bf-palette-box">
        <div class="bf-palette-input">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="text" x-ref="input" x-model="query" @input="index = 0"
                @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose(results[index])"
                placeholder="<?= lang('Bonfire.paletteHint') ?>" aria-label="<?= lang('Bonfire.paletteHint') ?>">
            <kbd class="bf-kbd">esc</kbd>
        </div>
        <div class="bf-palette-results" role="listbox">
            <template x-for="(result, i) in results" :key="i">
                <button type="button" class="bf-palette-row" role="option" :class="{ active: i === index }" :aria-selected="i === index"
                    @mouseenter="index = i" @click="choose(result)">
                    <i class="fas" :class="result.search ? 'fa-search' : 'fa-arrow-right'" aria-hidden="true"></i>
                    <span class="bf-palette-label" x-text="result.label"></span>
                    <span class="bf-palette-hint" x-text="result.hint"></span>
                </button>
            </template>
            <div class="bf-palette-empty" x-show="results.length === 0"><?= lang('Bonfire.noResults') ?></div>
        </div>
    </div>
</div>
