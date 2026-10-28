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

// The checked row checkboxes of a list's form. Select-all also ticks disabled rows, which never post.
function bfCheckedRows(form) {
    return form.querySelectorAll('tbody input[type="checkbox"]:checked:not(:disabled)').length;
}

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
                .slice(0, needle ? 8 : undefined);

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
                this.count = bfCheckedRows(this.form);
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

    // <x-confirm-dialog>: asks before a [data-confirm] button or link, or an hx-confirm request, goes ahead.
    Alpine.data('bfConfirmDialog', () => ({
        title: '',
        body: '',
        button: '',
        danger: false,
        typeCount: false,
        count: 1,
        typed: '',
        proceed: null,

        init() {
            document.addEventListener('click', (event) => {
                const el = event.target.closest('[data-confirm]');
                if (! el) {
                    return;
                }
                event.preventDefault();
                this.ask(el.dataset, this.countFor(el), () => this.go(el));
            });

            document.addEventListener('htmx:confirm', (event) => {
                if (! event.detail.question) {
                    return;
                }
                event.preventDefault();
                this.ask({ confirm: event.detail.question }, 1, () => event.detail.issueRequest(true));
            });
        },

        // A bulk button acts on its form's checked rows; anything else on one record.
        countFor(el) {
            return el.dataset.confirmBulk ? bfCheckedRows(el.form) : 1;
        },

        ask(data, count, proceed) {
            this.count = count;
            this.title = data.confirm.replaceAll('{count}', count);
            this.body = data.confirmBody || '';
            this.button = data.confirmButton || this.$root.dataset.defaultButton;
            this.danger = data.confirmDanger === '1';
            this.typeCount = data.confirmType === '1';
            this.typed = '';
            this.proceed = proceed;
            this.$root.showModal();
        },

        get typeLabel() {
            return this.$root.dataset.typeLabel.replace('%s', this.count);
        },

        accept() {
            this.$root.close();
            this.proceed();
        },

        // Submits the button's form the way its click would have, marked as confirmed, or follows the link.
        go(el) {
            if (! el.form) {
                window.location.href = el.href;
                return;
            }
            const fields = { confirmed: '1' };
            if (this.typeCount) {
                fields.confirm_count = this.typed;
            }
            Object.entries(fields).forEach(([name, value]) => {
                const input = el.form.querySelector(`input[type="hidden"][name="${name}"]`) || el.form.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name }));
                input.value = value;
            });
            el.form.requestSubmit(el);
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
