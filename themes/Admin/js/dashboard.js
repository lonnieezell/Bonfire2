/**
 * The dashboard editor: Customize lets the user drag widgets into a new
 * order, group them into sections, size, remove and add them from the
 * palette; Save stores the result as their own layout, Cancel reloads
 * the page as it was.
 *
 * The tiles and sections on the page are the layout being edited:
 * SortableJS and the controls below move them, and Save reads them.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardEditor', () => ({
        editing: false,
        saving: false,
        failed: false,
        sectionCount: 0,
        unplaced: 0,
        search: '',
        sortables: [],

        start() {
            this.failed = false;
            this.editing = true;
            this.sectionCount = this.sections().length;

            this.sortables.push(Sortable.create(this.$root.querySelector('[data-sections]'), {
                handle: '.bf-section-handle',
                draggable: '.bf-dashboard-section',
                animation: 150,
            }));
            this.grids().forEach((grid) => this.sortGrid(grid));
            this.$root.querySelectorAll('[data-palette-list]').forEach((list) => {
                this.sortables.push(Sortable.create(list, {
                    group: { name: 'bf-dashboard', pull: 'clone', put: false },
                    sort: false,
                    draggable: '.bf-palette-entry',
                }));
            });
            this.countUnplaced();
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
            this.sectionCount = this.sections().length;
            this.$nextTick(() => section.querySelector('[data-section-title]').focus());
        },

        // Its widgets move to the previous section, or the next one when it was first.
        removeSection(button) {
            const section = button.closest('[data-dashboard-section]');
            const sections = this.sections();
            const index = sections.indexOf(section);
            const target = sections[index === 0 ? 1 : index - 1];

            if (! target) {
                return;
            }

            target.querySelector('[data-section]').append(...section.querySelectorAll('[data-section] > .bf-widget'));
            section.remove();
            this.sectionCount = this.sections().length;
        },

        // Appends the entry's placeholder tile to the first section.
        addWidget(button) {
            if (this.sections().length === 0) {
                this.addSection();
            }

            const entry = button.closest('.bf-palette-entry');

            this.grids()[0].append(this.placeholder(entry));
            entry.hidden = true;
            this.countUnplaced();
        },

        removeWidget(button) {
            const tile = button.closest('.bf-widget');

            this.paletteEntry(tile.dataset.widgetId).hidden = false;
            tile.remove();
            this.countUnplaced();
        },

        matches(entry) {
            return entry.dataset.search.includes(this.search.trim().toLowerCase());
        },

        get allPlaced() {
            return this.unplaced === 0;
        },

        resize(option) {
            const tile = option.closest('.bf-widget');

            tile.className = tile.className.replace(/\bbf-span-\d+\b/, `bf-span-${option.dataset.columns}`);
            tile.dataset.size = option.dataset.sizeOption;
        },

        sortGrid(grid) {
            this.sortables.push(Sortable.create(grid, {
                group: 'bf-dashboard',
                handle: '.bf-widget-handle',
                draggable: '.bf-widget',
                animation: 150,
                // A palette entry dropped here becomes its placeholder tile
                onAdd: (event) => {
                    if (! event.item.matches('.bf-palette-entry')) {
                        return;
                    }
                    event.item.replaceWith(this.placeholder(event.item));
                    event.clone.hidden = true;
                    this.countUnplaced();
                },
            }));
        },

        placeholder(entry) {
            return entry.querySelector('template[data-placeholder]').content.firstElementChild.cloneNode(true);
        },

        paletteEntry(id) {
            return this.$root.querySelector(`.bf-palette-entry[data-palette-widget="${CSS.escape(id)}"]`);
        },

        countUnplaced() {
            this.unplaced = this.$root.querySelectorAll('.bf-palette-entry:not([hidden])').length;
        },

        sections() {
            return [...this.$root.querySelectorAll('[data-sections] > [data-dashboard-section]')];
        },

        grids() {
            return this.sections().map((section) => section.querySelector('[data-section]'));
        },

        // The layout as the page currently shows it, in the stored format.
        read() {
            return {
                version: 1,
                sections: this.sections().map((section) => ({
                    title: section.querySelector('[data-section-title]').value.trim(),
                    widgets: [...section.querySelectorAll('[data-section] > .bf-widget')].map((tile) => ({
                        id: tile.dataset.widgetId,
                        size: tile.dataset.size,
                    })),
                })),
            };
        },
    }));
});
