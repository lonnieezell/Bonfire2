/**
 * The dashboard editor: Customize lets the user drag widgets into a new
 * order, group them into sections, size, remove and add them from the
 * palette; Save stores the result as their own layout, Cancel reloads
 * the page as it was. Every change can also be made from the tile and
 * section menus, and is announced to screen readers.
 *
 * The tiles and sections on the page are the layout being edited:
 * SortableJS and the controls below move them, refresh() updates what
 * the controls show, and Save reads them.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardEditor', () => ({
        editing: false,
        saving: false,
        failed: false,
        sectionCount: 0,
        sectionNames: [],
        unplaced: 0,
        search: '',
        target: 0,
        announcement: '',
        // Bumped on every change, so bindings that read the page re-evaluate
        revision: 0,
        sortables: [],

        start() {
            this.failed = false;
            this.editing = true;

            this.sortables.push(Sortable.create(this.$root.querySelector('[data-sections]'), {
                handle: '.bf-section-handle',
                draggable: '.bf-dashboard-section',
                animation: 150,
                onEnd: () => this.refresh(),
            }));
            this.grids().forEach((grid) => this.sortGrid(grid));
            this.$root.querySelectorAll('[data-palette-list]').forEach((list) => {
                this.sortables.push(Sortable.create(list, {
                    group: { name: 'bf-dashboard', pull: 'clone', put: false },
                    sort: false,
                    draggable: '.bf-palette-entry',
                }));
            });
            this.refresh();
        },

        cancel() {
            window.location.reload();
        },

        async save() {
            this.saving = true;
            this.failed = false;

            const response = await fetch(this.$root.dataset.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    [this.$root.dataset.csrfHeader]: this.$root.dataset.csrfHash,
                },
                body: JSON.stringify(this.read()),
            }).catch(() => null);

            if (response?.ok) {
                window.location.reload();
                return;
            }

            this.saving = false;
            this.failed = true;
        },

        addSection() {
            const section = this.$refs.newSection.content.firstElementChild.cloneNode(true);

            this.$root.querySelector('[data-sections]').append(section);
            this.sortGrid(section.querySelector('[data-section]'));
            this.refresh();
            this.announce('sectionAdded', this.sectionNames.at(-1));
            this.$nextTick(() => section.querySelector('[data-section-title]').focus());
        },

        // Its widgets move to the previous section, or the next one when it was first.
        removeSection(button) {
            const section = button.closest('[data-dashboard-section]');
            const index = this.sections().indexOf(section);
            const target = this.sections()[index === 0 ? 1 : index - 1];

            if (! target) {
                return;
            }

            target.querySelector('[data-section]').append(...this.tiles(section));
            section.remove();
            this.refresh();
            this.announce('sectionDeleted', this.sectionNames[this.sections().indexOf(target)]);
            target.querySelector('[data-section-title]').focus();
        },

        moveSection(button, step) {
            const section = button.closest('[data-dashboard-section]');
            const sections = this.sections();
            const index = sections.indexOf(section) + step;

            if (step < 0) {
                sections[index].before(section);
            } else {
                sections[index].after(section);
            }

            this.refresh();
            this.announce('sectionMoved', this.sectionNames[index], index + 1);
            button.focus();
        },

        canMoveSection(button, step) {
            const index = this.sections().indexOf(button.closest('[data-dashboard-section]')) + step;

            return index >= 0 && index < this.sectionCount;
        },

        // Appends the entry's placeholder tile to the chosen section.
        addWidget(button) {
            if (this.sections().length === 0) {
                this.addSection();
            }

            const entry = button.closest('.bf-palette-entry');
            const tile = this.placeholder(entry);
            const index = Math.min(this.target, this.sections().length - 1);

            this.grids()[index].append(tile);
            entry.hidden = true;
            this.refresh();
            this.announce('added', tile.dataset.title, this.sectionNames[index]);
            this.focusTile(tile);
        },

        removeWidget(button) {
            const tile = button.closest('.bf-widget');

            this.paletteEntry(tile.dataset.widgetId).hidden = false;
            tile.remove();
            this.refresh();
            this.announce('removed', tile.dataset.title);
        },

        // One place earlier or later within its section.
        move(item, step) {
            const tile = item.closest('.bf-widget');
            const tiles = this.tiles(tile.closest('[data-dashboard-section]'));
            const index = tiles.indexOf(tile) + step;

            if (step < 0) {
                tiles[index].before(tile);
            } else {
                tiles[index].after(tile);
            }

            this.moved(tile);
        },

        canMove(item, step) {
            const tile = item.closest('.bf-widget');
            const tiles = this.tiles(tile.closest('[data-dashboard-section]'));
            const index = tiles.indexOf(tile) + step;

            return index >= 0 && index < tiles.length;
        },

        moveTo(item, index) {
            const tile = item.closest('.bf-widget');

            this.grids()[index].append(tile);
            this.moved(tile);
        },

        sectionIndex(item) {
            return this.sections().indexOf(item.closest('[data-dashboard-section]'));
        },

        moved(tile) {
            const section = tile.closest('[data-dashboard-section]');

            this.refresh();
            this.announce('moved', tile.dataset.title, this.tiles(section).indexOf(tile) + 1, this.sectionNames[this.sectionIndex(tile)]);
            this.focusTile(tile);
        },

        resize(option) {
            const tile = option.closest('.bf-widget');

            tile.className = tile.className.replace(/\bbf-span-\d+\b/, `bf-span-${option.dataset.columns}`);
            tile.dataset.size = option.dataset.sizeOption;
            this.announce('resized', tile.dataset.title, option.textContent.trim());
        },

        matches(entry) {
            return entry.dataset.search.includes(this.search.trim().toLowerCase());
        },

        get allPlaced() {
            return this.unplaced === 0;
        },

        // Brings the controls up to date with the page.
        refresh() {
            const untitled = this.messages().untitled;

            this.sectionNames = this.sections().map((section, i) => section.querySelector('[data-section-title]').value.trim()
                || untitled.replace('{0}', i + 1));
            this.sectionCount = this.sectionNames.length;
            this.target = Math.min(this.target, Math.max(0, this.sectionCount - 1));
            this.unplaced = this.$root.querySelectorAll('.bf-palette-entry:not([hidden])').length;
            this.revision++;
        },

        announce(key, ...values) {
            const message = values.reduce((text, value, i) => text.replace(`{${i}}`, value), this.messages()[key]);

            // Cleared first, so the same message twice is still read out
            this.announcement = '';
            this.$nextTick(() => { this.announcement = message; });
        },

        messages() {
            return JSON.parse(this.$root.dataset.messages);
        },

        focusTile(tile) {
            this.$nextTick(() => tile.querySelector('.bf-widget-handle').focus());
        },

        sortGrid(grid) {
            this.sortables.push(Sortable.create(grid, {
                group: 'bf-dashboard',
                handle: '.bf-widget-handle',
                draggable: '.bf-widget',
                animation: 150,
                onEnd: () => this.refresh(),
                // A palette entry dropped here becomes its placeholder tile
                onAdd: (event) => {
                    if (! event.item.matches('.bf-palette-entry')) {
                        return;
                    }
                    event.item.replaceWith(this.placeholder(event.item));
                    event.clone.hidden = true;
                    this.refresh();
                },
            }));
        },

        placeholder(entry) {
            return entry.querySelector('template[data-placeholder]').content.firstElementChild.cloneNode(true);
        },

        paletteEntry(id) {
            return this.$root.querySelector(`.bf-palette-entry[data-palette-widget="${CSS.escape(id)}"]`);
        },

        sections() {
            return [...this.$root.querySelectorAll('[data-sections] > [data-dashboard-section]')];
        },

        grids() {
            return this.sections().map((section) => section.querySelector('[data-section]'));
        },

        tiles(section) {
            return [...section.querySelectorAll('[data-section] > .bf-widget')];
        },

        // The layout as the page currently shows it, in the stored format.
        read() {
            return {
                version: 1,
                sections: this.sections().map((section) => ({
                    title: section.querySelector('[data-section-title]').value.trim(),
                    widgets: this.tiles(section).map((tile) => ({
                        id: tile.dataset.widgetId,
                        size: tile.dataset.size,
                    })),
                })),
            };
        },
    }));
});
