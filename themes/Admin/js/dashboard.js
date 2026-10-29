/**
 * The dashboard editor: Customize lets the user drag widgets into a new
 * order, group them into sections, size, remove and add them from the
 * palette, and change each widget's settings; Save stores the result as their own layout, Cancel puts the
 * page back as it was. Every change can be undone, can also be made from
 * the keyboard, and is announced to screen readers.
 *
 * The tiles and sections on the page are the layout being edited:
 * SortableJS and the controls below move them, then commit() records the
 * layout they show. Undo and redo put a recorded layout back on the page
 * with apply(), reusing the same tile elements.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardEditor', () => {
        // Kept from init(): $root is found from the element that ran the handler, which may be a tile just taken off the page
        let root;
        // The tile whose settings are open
        let settingsTile = null;

        return {
            editing: false,
            saving: false,
            failed: false,
            library: false,
            // The settings panel is open, in the palette's place
            configuring: false,
            settingsTitle: '',
            settingsIcon: '',
            // A drag or resize is under way: the column guides show stronger
            arranging: false,
            sectionNames: [],
            search: '',
            target: 0,
            announcement: '',
            toast: '',
            toastUndo: false,
            messages: {},
            sizeColumns: {},
            // Layouts in the stored format: as loaded, as shown, and the ones undo and redo go to
            saved: null,
            current: null,
            past: [],
            future: [],
            // Tiles taken off the page, by widget id, so putting one back keeps its content
            removed: new Map(),
            // Bumped on every change, so bindings that read the page re-evaluate
            revision: 0,

            init() {
                root = this.$root;
                this.messages = JSON.parse(root.dataset.messages);
                this.sizeColumns = JSON.parse(root.dataset.sizeColumns);

                const palette = root.querySelector('#bf-widget-palette');
                palette.addEventListener('show.bs.offcanvas', () => { this.library = true; });
                palette.addEventListener('hide.bs.offcanvas', () => { this.library = false; });

                const settings = root.querySelector('#bf-widget-settings');
                settings.addEventListener('show.bs.offcanvas', () => { this.configuring = true; });
                settings.addEventListener('hide.bs.offcanvas', () => {
                    this.configuring = false;
                    settingsTile?.classList.remove('is-configuring');
                    settingsTile = null;
                });
            },

            start() {
                // Cancel keeps the page, so the Sortable instances are only made once
                if (! this.saved) {
                    this.grids().forEach((grid) => this.sortGrid(grid));
                    root.querySelectorAll('[data-palette-list]').forEach((list) => {
                        Sortable.create(list, {
                            group: { name: 'bf-dashboard', pull: 'clone', put: false },
                            sort: false,
                            draggable: '.bf-palette-entry',
                            filter: '[data-placed], button',
                            preventOnFilter: false,
                            onStart: () => { this.arranging = true; },
                            onEnd: () => { this.arranging = false; },
                        });
                    });
                    this.saved = this.read();
                }

                this.failed = false;
                this.current = this.read();
                this.past = [];
                this.future = [];
                this.editing = true;
                this.refresh();
            },

            cancel() {
                this.apply(this.saved);
                this.stop();
            },

            stop() {
                this.editing = false;
                this.toast = '';
                this.closeLibrary();
                this.closeSettings();
            },

            dirty() {
                return JSON.stringify(this.current) !== JSON.stringify(this.saved);
            },

            async save() {
                if (! this.dirty()) {
                    this.stop();
                    return;
                }

                await this.post(root.dataset.saveUrl);
            },

            // Stores the layout as shown as the default of every user who has not saved their own.
            async saveSite() {
                await this.post(root.dataset.siteSaveUrl);
            },

            async post(url) {
                this.saving = true;
                this.failed = false;

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        [root.dataset.csrfHeader]: root.dataset.csrfHash,
                    },
                    body: JSON.stringify(this.current),
                }).catch(() => null);

                // A redirect (to the login page, when the session expired) saved nothing
                if (response?.ok && ! response.redirected) {
                    window.location.reload();
                    return;
                }

                // A rejected layout still rotated the CSRF token: take the new one for the retry
                const csrf = (await response?.json().catch(() => null))?.csrf;
                if (csrf) {
                    root.dataset.csrfHash = csrf;
                }

                this.saving = false;
                this.failed = true;
            },

            // Records the layout the page now shows, after a change to it.
            commit() {
                const layout = this.read();

                if (JSON.stringify(layout) !== JSON.stringify(this.current)) {
                    this.past = [...this.past.slice(-49), this.current];
                    this.future = [];
                    this.current = layout;
                }
                this.refresh();
            },

            undo() {
                if (! this.past.length) {
                    return;
                }

                this.future = [this.current, ...this.future];
                this.current = this.past.at(-1);
                this.past = this.past.slice(0, -1);
                this.apply(this.current);
                this.notify(this.messages.undone);
            },

            redo() {
                if (! this.future.length) {
                    return;
                }

                this.past = [...this.past, this.current];
                this.current = this.future[0];
                this.future = this.future.slice(1);
                this.apply(this.current);
                this.notify(this.messages.redone);
            },

            reset() {
                this.apply(JSON.parse(root.dataset.defaultLayout));
                this.commit();
                this.notify(this.messages.reset, true);
            },

            // Puts a recorded layout on the page.
            apply(layout) {
                // Its form may no longer show the tile's settings
                this.closeSettings();

                const placed = new Map(this.tiles().map((tile) => [tile.dataset.widgetId, tile]));
                const ids = new Set(layout.sections.flatMap((section) => section.widgets.map((widget) => widget.id)));

                placed.forEach((tile, id) => {
                    if (! ids.has(id)) {
                        this.takeOff(tile);
                    }
                });

                while (this.sections().length < layout.sections.length) {
                    root.querySelector('[data-sections]').append(this.newSection());
                }

                const sections = this.sections();
                layout.sections.forEach((data, i) => {
                    const grid = sections[i].querySelector('[data-section]');

                    sections[i].querySelector('[data-section-title]').value = data.title;
                    data.widgets.forEach((widget) => {
                        const tile = placed.get(widget.id) ?? this.tileFor(widget.id);

                        grid.append(tile);
                        this.setSize(tile, widget.size);
                        this.setSettings(tile, widget.settings ?? {});
                    });
                });
                // Emptied by now: every tile they held is elsewhere or off the page
                sections.slice(layout.sections.length).forEach((section) => section.remove());

                this.refresh();
            },

            addSection(button = null) {
                const section = this.newSection();
                const after = button?.closest('[data-dashboard-section]');

                after ? after.after(section) : root.querySelector('[data-sections]').append(section);
                this.commit();
                this.announce(this.message('sectionAdded', this.sectionNames[this.sections().indexOf(section)]));
                this.$nextTick(() => section.querySelector('[data-section-title]').focus());
            },

            // Its widgets go with it; undo brings them back.
            removeSection(button) {
                const section = button.closest('[data-dashboard-section]');
                const name = this.sectionNames[this.sections().indexOf(section)];

                this.tiles(section).forEach((tile) => this.takeOff(tile));
                section.remove();
                this.commit();
                this.notify(this.message('sectionDeleted', name), true);
            },

            moveSection(button, step) {
                const section = button.closest('[data-dashboard-section]');
                const index = this.shift(section, this.sections(), step);

                this.commit();
                this.announce(this.message('sectionMoved', this.sectionNames[index], index + 1));
                this.$nextTick(() => section.querySelector('[data-section-title]').focus());
            },

            canMoveSection(button, step) {
                const index = this.sections().indexOf(button.closest('[data-dashboard-section]')) + step;

                return index >= 0 && index < this.sections().length;
            },

            widgetCount(el) {
                const count = this.tiles(el.closest('[data-dashboard-section]')).length;

                return count === 1 ? this.messages.widgetCountOne : this.message('widgetCount', count);
            },

            // Opens the palette, adding to the button's section when it has one.
            openLibrary(button = null) {
                const section = button?.closest('[data-dashboard-section]');

                if (section) {
                    this.target = this.sections().indexOf(section);
                }
                this.closeSettings();
                bootstrap.Offcanvas.getOrCreateInstance('#bf-widget-palette').show();
            },

            closeLibrary() {
                bootstrap.Offcanvas.getInstance('#bf-widget-palette')?.hide();
            },

            // Opens the tile's settings in the palette's place: its own values, else what each field inherits.
            openSettings(button) {
                const tile = button.closest('.bf-widget');
                const template = this.settingsTemplate(tile);

                this.closeLibrary();
                settingsTile?.classList.remove('is-configuring');
                settingsTile = tile;
                tile.classList.add('is-configuring');
                this.settingsTitle = template.dataset.title;
                this.settingsIcon = template.dataset.icon;
                root.querySelector('[data-settings-fields]').replaceChildren(template.content.firstElementChild.cloneNode(true));
                this.fillSettings(JSON.parse(tile.dataset.settings ?? '{}'));
                bootstrap.Offcanvas.getOrCreateInstance('#bf-widget-settings').show();
            },

            closeSettings() {
                bootstrap.Offcanvas.getInstance('#bf-widget-settings')?.hide();
            },

            // Keeps on the tile the values that differ from what their fields inherit.
            applySettings() {
                const tile = settingsTile;
                const form = root.querySelector('#bf-widget-settings form');
                const own = {};

                this.settingsFields(tile).forEach((field) => {
                    const input = form.elements[field.name];
                    const value = { toggle: () => input.checked, number: () => Number(input.value), text: () => input.value.trim() }[field.type]?.() ?? input.value;

                    if (value !== field.inherited) {
                        own[field.name] = value;
                    }
                });

                const before = this.current;

                this.setSettings(tile, own);
                this.closeSettings();
                this.commit();
                // Applying unchanged values records nothing, so there is nothing to undo
                if (this.current !== before) {
                    this.notify(this.message('settings', tile.dataset.title), true);
                }
                this.focusTile(tile);
            },

            // Fills the form with what each field inherits; Apply keeps it.
            resetSettings() {
                this.fillSettings({});
            },

            fillSettings(own) {
                const form = root.querySelector('#bf-widget-settings form');

                this.settingsFields(settingsTile).forEach((field) => {
                    const input = form.elements[field.name];
                    const value = own[field.name] ?? field.inherited;

                    // A segmented choice is a RadioNodeList, whose value checks the matching radio
                    field.type === 'toggle' ? (input.checked = value) : (input.value = value);
                });
            },

            // Whether the tile's settings differ from those it had when the page loaded.
            settingsChanged(el) {
                const tile = el.closest('.bf-widget');
                const saved = this.saved?.sections.flatMap((section) => section.widgets).find((widget) => widget.id === tile.dataset.widgetId);

                return JSON.stringify(saved?.settings ?? {}) !== JSON.stringify(JSON.parse(tile.dataset.settings ?? '{}'));
            },

            // A tile without settings of its own carries none.
            setSettings(tile, own) {
                if (Object.keys(own).length) {
                    tile.dataset.settings = JSON.stringify(own);
                } else {
                    delete tile.dataset.settings;
                }
            },

            settingsTemplate(tile) {
                return root.querySelector(`template[data-settings-for="${CSS.escape(tile.dataset.widgetId)}"]`);
            },

            settingsFields(tile) {
                return JSON.parse(this.settingsTemplate(tile).dataset.fields);
            },

            // Appends the entry's widget to the chosen section.
            addWidget(button) {
                if (this.sections().length === 0) {
                    root.querySelector('[data-sections]').append(this.newSection());
                    this.refresh();
                }

                const index = Math.min(this.target, this.sections().length - 1);
                const tile = this.tileFor(button.closest('.bf-palette-entry').dataset.paletteWidget);

                this.grids()[index].append(tile);
                this.commit();
                this.notify(this.message('added', tile.dataset.title, this.sectionNames[index]));
                this.focusTile(tile);
            },

            // Focus goes to the next tile, else the previous one, else the section heading.
            removeWidget(el) {
                const tile = el.closest('.bf-widget');
                const section = tile.closest('[data-dashboard-section]');
                const tiles = this.tiles(section);
                const neighbour = tiles[tiles.indexOf(tile) + 1] ?? tiles[tiles.indexOf(tile) - 1];

                this.takeOff(tile);
                this.commit();
                this.notify(this.message('removed', tile.dataset.title), true);
                neighbour ? this.focusTile(neighbour) : section.querySelector('[data-section-title]').focus();
            },

            resize(option) {
                const tile = option.closest('.bf-widget');

                this.setSize(tile, option.dataset.sizeOption);
                this.resized(tile);
            },

            resized(tile) {
                this.commit();
                this.announce(this.message('resized', tile.dataset.title, this.sizeName(tile)));
            },

            // Drags the corner: the width snaps to the nearest of the widget's sizes.
            startResize(event) {
                event.preventDefault();
                event.stopPropagation();

                const tile = event.target.closest('.bf-widget');
                const grid = tile.parentElement;
                const gap = parseFloat(getComputedStyle(grid).columnGap) || 0;
                const column = (grid.clientWidth - 11 * gap) / 12;
                const left = tile.getBoundingClientRect().left;
                const sizes = tile.dataset.sizes.split(' ');
                const before = tile.dataset.size;

                const move = (e) => {
                    const wanted = (e.clientX - left + gap) / (column + gap);
                    const nearest = sizes.reduce((best, size) => (Math.abs(this.sizeColumns[size] - wanted) < Math.abs(this.sizeColumns[best] - wanted) ? size : best));

                    this.setSize(tile, nearest);
                    tile.dataset.sizeLabel = this.sizeName(tile, true);
                };
                const up = () => {
                    window.removeEventListener('pointermove', move);
                    tile.classList.remove('is-resizing');
                    this.arranging = false;
                    if (tile.dataset.size !== before) {
                        this.resized(tile);
                    }
                };

                tile.classList.add('is-resizing');
                tile.dataset.sizeLabel = this.sizeName(tile, true);
                this.arranging = true;
                tile.focus();
                window.addEventListener('pointermove', move);
                window.addEventListener('pointerup', up, { once: true });
            },

            // Arrows move the focused tile, through the sections; Shift+Left and Right resize it.
            tileKey(event) {
                const tile = event.currentTarget;

                if (! this.editing || event.target !== tile) {
                    return;
                }

                if (event.key === 'Delete' || event.key === 'Backspace') {
                    event.preventDefault();
                    this.removeWidget(tile);
                    return;
                }

                if (event.shiftKey) {
                    const step = { ArrowLeft: -1, ArrowRight: 1 }[event.key];
                    const sizes = tile.dataset.sizes.split(' ');
                    const size = sizes[sizes.indexOf(tile.dataset.size) + (step ?? 0)];

                    if (step && size) {
                        event.preventDefault();
                        this.setSize(tile, size);
                        this.resized(tile);
                    }
                    return;
                }

                if (! ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
                    return;
                }
                event.preventDefault();

                const grids = this.grids();
                const index = grids.indexOf(tile.parentElement);
                const siblings = this.tiles(tile.closest('[data-dashboard-section]'));
                const position = siblings.indexOf(tile);

                if (event.key === 'ArrowLeft' && position > 0) {
                    siblings[position - 1].before(tile);
                } else if (event.key === 'ArrowRight' && position < siblings.length - 1) {
                    siblings[position + 1].after(tile);
                } else if ((event.key === 'ArrowLeft' || event.key === 'ArrowUp') && index > 0) {
                    grids[index - 1].append(tile);
                } else if ((event.key === 'ArrowRight' || event.key === 'ArrowDown') && index < grids.length - 1) {
                    grids[index + 1].prepend(tile);
                } else {
                    return;
                }

                this.moved(tile);
            },

            // Undo and redo, and Escape, anywhere but in a text field.
            key(event) {
                if (! this.editing || event.target.matches('input, textarea, select')) {
                    return;
                }

                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'z') {
                    event.preventDefault();
                    event.shiftKey ? this.redo() : this.undo();
                } else if (event.key === 'Escape' && ! this.library && ! this.configuring) {
                    document.activeElement?.closest('.bf-widget')?.blur();
                }
            },

            moved(tile) {
                const section = tile.closest('[data-dashboard-section]');

                this.commit();
                this.announce(this.message('moved', tile.dataset.title, this.tiles(section).indexOf(tile) + 1, this.sectionNames[this.sections().indexOf(section)]));
                this.focusTile(tile);
            },

            // Moves el step places among its siblings and returns its new index.
            shift(el, siblings, step) {
                const index = siblings.indexOf(el) + step;

                siblings[index][step < 0 ? 'before' : 'after'](el);

                return index;
            },

            setSize(tile, size) {
                tile.className = tile.className.replace(/\bbf-span-\d+\b/, `bf-span-${this.sizeColumns[size]}`);
                tile.dataset.size = size;
            },

            // The name of the tile's size, or its short label as on the size buttons.
            sizeName(tile, short = false) {
                const option = tile.querySelector(`[data-size-option="${tile.dataset.size}"]`);

                return short ? option?.textContent.trim() : option?.getAttribute('aria-label');
            },

            matches(entry) {
                return entry.dataset.search.includes(this.search.trim().toLowerCase());
            },

            noMatches() {
                return ! [...root.querySelectorAll('[data-palette-list] > .bf-palette-entry')].some((entry) => this.matches(entry));
            },

            groupMatches(group) {
                return [...group.querySelectorAll('.bf-palette-entry')].some((entry) => this.matches(entry));
            },

            // Brings the controls up to date with the page.
            refresh() {
                const placed = new Set(this.tiles().map((tile) => tile.dataset.widgetId));
                const entries = [...root.querySelectorAll('[data-palette-list] > .bf-palette-entry')];

                this.sectionNames = this.sections().map((section, i) => section.querySelector('[data-section-title]').value.trim()
                    || this.message('untitled', i + 1));
                this.target = Math.min(this.target, Math.max(0, this.sectionNames.length - 1));
                entries.forEach((entry) => entry.toggleAttribute('data-placed', placed.has(entry.dataset.paletteWidget)));
                this.revision++;
            },

            message(key, ...values) {
                // One pass with a function, so a title holding "{1}" or "$&" is read out as typed
                return this.messages[key].replace(/\{(\d+)\}/g, (placeholder, i) => values[i] ?? placeholder);
            },

            // Shows the message for a few seconds, with Undo when asked, and announces it.
            notify(message, undoable = false) {
                this.toast = message;
                this.toastUndo = undoable;
                clearTimeout(this.toastTimer);
                this.toastTimer = setTimeout(() => { this.toast = ''; }, 4000);
                this.announce(message);
            },

            announce(message) {
                // Cleared first, so the same message twice is still read out
                this.announcement = '';
                this.$nextTick(() => { this.announcement = message; });
            },

            focusTile(tile) {
                this.$nextTick(() => tile.focus());
            },

            sortGrid(grid) {
                Sortable.create(grid, {
                    group: 'bf-dashboard',
                    draggable: '.bf-widget',
                    filter: '.bf-widget-tools, .bf-widget-resize',
                    preventOnFilter: false,
                    animation: 150,
                    onStart: () => { this.arranging = true; },
                    onEnd: () => {
                        this.arranging = false;
                        this.commit();
                    },
                    // A palette entry dropped here becomes its widget's tile
                    onAdd: (event) => {
                        if (! event.item.matches('.bf-palette-entry')) {
                            return;
                        }
                        const tile = this.tileFor(event.item.dataset.paletteWidget);

                        event.item.replaceWith(tile);
                        this.commit();
                        this.notify(this.message('added', tile.dataset.title, this.sectionNames[this.grids().indexOf(tile.parentElement)]));
                    },
                });
            },

            newSection() {
                const section = root.querySelector('[x-ref="newSection"]').content.firstElementChild.cloneNode(true);

                this.sortGrid(section.querySelector('[data-section]'));

                return section;
            },

            takeOff(tile) {
                if (tile === settingsTile) {
                    this.closeSettings();
                }
                this.removed.set(tile.dataset.widgetId, tile);
                tile.remove();
            },

            // The tile taken off earlier, else the palette entry's placeholder; at the widget's default size.
            tileFor(id) {
                const entry = root.querySelector(`.bf-palette-entry[data-palette-widget="${CSS.escape(id)}"]`);
                const placeholder = entry.querySelector('template[data-placeholder]').content.firstElementChild;
                const tile = this.removed.get(id) ?? placeholder.cloneNode(true);

                this.setSize(tile, placeholder.dataset.size);

                return tile;
            },

            sections() {
                return [...root.querySelectorAll('[data-sections] > [data-dashboard-section]')];
            },

            grids() {
                return this.sections().map((section) => section.querySelector('[data-section]'));
            },

            tiles(section = root.querySelector('[data-sections]')) {
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
                            ...(tile.dataset.settings ? { settings: JSON.parse(tile.dataset.settings) } : {}),
                        })),
                    })),
                };
            },
        };
    });
});
