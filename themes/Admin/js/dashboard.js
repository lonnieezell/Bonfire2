/**
 * The dashboard editor: Customize lets the user drag widgets into a new
 * order, Save stores it as their own layout, Cancel puts it back.
 *
 * `sections` holds the layout being edited; SortableJS moves the tiles
 * and each drop reads the new order back into it.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardEditor', () => ({
        editing: false,
        saving: false,
        failed: false,
        sections: [],
        snapshot: [],
        sortables: [],

        start() {
            this.sections = this.read();
            this.snapshot = JSON.parse(JSON.stringify(this.sections));
            this.failed = false;
            this.editing = true;

            this.grids().forEach((grid) => {
                this.sortables.push(Sortable.create(grid, {
                    handle: '.bf-widget-handle',
                    draggable: '.bf-widget',
                    animation: 150,
                    onEnd: () => { this.sections = this.read(); },
                }));
            });
        },

        cancel() {
            this.place(this.snapshot);
            this.stop();
        },

        stop() {
            this.sortables.forEach((sortable) => sortable.destroy());
            this.sortables = [];
            this.editing = false;
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
                body: JSON.stringify({ version: 1, sections: this.sections }),
            }).catch(() => null);

            if (response?.ok) {
                window.location.reload();
                return;
            }

            this.saving = false;
            this.failed = true;
        },

        grids() {
            return [...this.$root.querySelectorAll('[data-section]')];
        },

        // The layout as the tiles currently stand.
        read() {
            return this.grids().map((grid) => ({
                title: grid.closest('.bf-dashboard-section').querySelector('.bf-dashboard-section-title')?.textContent.trim() ?? '',
                widgets: [...grid.querySelectorAll(':scope > .bf-widget')].map((tile) => ({
                    id: tile.dataset.widgetId,
                    size: tile.dataset.size,
                })),
            }));
        },

        // Moves the tiles into the given layout.
        place(sections) {
            const grids = this.grids();

            sections.forEach((section, i) => section.widgets.forEach((widget) => {
                grids[i].append(this.$root.querySelector(`.bf-widget[data-widget-id="${CSS.escape(widget.id)}"]`));
            }));
        },
    }));
});
