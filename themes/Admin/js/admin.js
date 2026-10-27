/**
 * Select All checkbox for data tables
 * using plain javascript
 */
function toggleSelectAll(checkbox) {
    var table = checkbox.closest('table')
    var checkboxes = table.getElementsByTagName('input')

    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].type == 'checkbox') {
            checkboxes[i].checked = checkbox.checked
        }
    }
}

// Function to attach the event listener
function attachSelectAllListener() {
    const selectAllElement = document.querySelector('.select-all');

    // Attach the event listener only if the element exists
    if (selectAllElement) {
        selectAllElement.addEventListener('click', function (e) {
            toggleSelectAll(e.target);
        });
    }
}

// Initial attachment
attachSelectAllListener();

// Re-attach the event listener after each htmx request
document.body.addEventListener('htmx:afterSettle', function () {
    attachSelectAllListener();
});

// function for recycler get requests issued directly from select box
function sendRecyclerGetRequest(selectedValue) {
    const url = new URL(window.location.href);
    url.searchParams.set('r', selectedValue);
    fetch(url.toString()).then(response => {
        if (response.ok) {
            return response.text();
        }
        throw new Error('Network response was not ok.');
    }).then(html => {
        document.body.innerHTML = html; // Replace the whole page content
        window.history.pushState(null, null, url.toString()); // Update the URL
    }).catch(error => {
        console.error('Error fetching data:', error);
    });
}
// Color mode, remembered per viewer. master.php applies it before first paint.
window.bfTheme = {
    set(mode) {
        document.documentElement.dataset.bsTheme = mode;
        try {
            localStorage.setItem('bf-theme', mode);
        } catch (e) {}
        document.dispatchEvent(new CustomEvent('bf-theme', { detail: mode }));
    },
    toggle() {
        this.set(document.documentElement.dataset.bsTheme === 'dark' ? 'light' : 'dark');
    },
};

// Sidebar collapse, remembered per viewer. master.php applies it before first paint.
window.bfSidebar = {
    toggle() {
        const collapsed = document.documentElement.classList.toggle('bf-sidebar-collapsed');
        try {
            localStorage.setItem('bf-sidebar', collapsed ? 'collapsed' : 'expanded');
        } catch (e) {}
    },
};

// The ⌘K command palette (_palette.php): jump to a menu page, or search.
document.addEventListener('alpine:init', () => {
    Alpine.data('bfPalette', () => ({
        open: false,
        query: '',
        index: 0,
        items: [],

        init() {
            this.items = JSON.parse(document.getElementById('bf-palette-items').textContent);

            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    this.show();
                }
            });

            // Clicking the header's search box opens the palette. Tabbing into it keeps it a plain search field.
            const search = document.querySelector('.bf-search [name="search_term"]');
            search?.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this.show();
            });

            // The shortcut hint reads Ctrl K off macOS.
            const hint = document.querySelector('.bf-search .bf-kbd');
            if (hint && !/Mac|iPhone|iPad/.test(navigator.platform)) {
                hint.textContent = 'Ctrl K';
            }
        },

        get results() {
            const term = this.query.trim();
            const needle = term.toLowerCase();
            const matches = this.items
                .filter((item) => !needle || (item.label + ' ' + item.hint).toLowerCase().includes(needle))
                .slice(0, 8);

            if (term) {
                matches.push({ label: this.$root.dataset.searchLabel.replace('%s', () => term), hint: '', search: true });
            }

            return matches;
        },

        show() {
            if (!this.open) {
                this.returnFocus = document.activeElement;
            }
            this.query = '';
            this.index = 0;
            this.open = true;
            this.$nextTick(() => this.$refs.input.focus());
        },

        close() {
            if (!this.open) {
                return;
            }
            this.open = false;
            this.returnFocus?.focus?.();
        },

        move(step) {
            this.index = Math.max(0, Math.min(this.index + step, this.results.length - 1));
        },

        choose(result) {
            if (!result) {
                return;
            }
            if (result.search) {
                const form = document.querySelector('.bf-search');
                form.elements.search_term.value = this.query.trim();
                form.submit();
                return;
            }
            window.location.href = result.url;
        },
    }));

    // <x-selection-bar>: counts the checked row checkboxes in its form.
    Alpine.data('bfSelectionBar', () => ({
        count: 0,

        init() {
            this.form = this.$root.closest('form');
            const recount = () => {
                // Select-all also ticks disabled rows, which never post.
                this.count = this.form.querySelectorAll('tbody input[type="checkbox"]:checked:not(:disabled)').length;
            };
            // An htmx swap replaces the bar along with the rows, so the new bar counts afresh.
            this.form.addEventListener('change', recount);
            recount();
        },

        get label() {
            return this.$root.dataset.label.replace('%s', this.count);
        },

        clear() {
            this.form.querySelectorAll('input[type="checkbox"]').forEach((box) => {
                box.checked = false;
            });
            this.count = 0;
        },
    }));

    // <x-save-bar>: shows once its form differs from how it loaded.
    Alpine.data('bfSaveBar', () => ({
        dirty: false,

        init() {
            this.form = this.$root.closest('form');
            this.initial = this.snapshot();
            const check = () => {
                this.dirty = this.snapshot() !== this.initial;
            };
            this.form.addEventListener('input', check);
            this.form.addEventListener('change', check);
        },

        snapshot() {
            return JSON.stringify([...new FormData(this.form)].map(([key, value]) => [key, value.name ?? value]));
        },

        discard() {
            const fields = [...this.form.querySelectorAll('input, select, textarea')];
            const state = (field) => field.value + '|' + field.checked;
            const before = fields.map(state);
            this.form.reset();
            // Let x-model and other listeners see the restored values. Only changed fields,
            // so change-triggered requests (hx-trigger) don't fire for every field.
            fields.forEach((field, i) => {
                if (state(field) === before[i]) {
                    return;
                }
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });
            this.dirty = this.snapshot() !== this.initial;
        },
    }));
});
