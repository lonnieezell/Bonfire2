<script>
    // What the input of a blocks field does in the browser. It's defined once, however many blocks fields the form has,
    // and before Alpine starts, which looks for it when it meets x-data="bfBlocks(…)".
    window.bfBlocks ??= (max, slot) => ({
        count: 0,

        get full() {
            return ! slot && max > 0 && this.count >= max;
        },

        init() {
            const list = this.$refs.list;
            this.count = list.children.length;

            // Adding, moving and deleting a block are changes the save bar learns about from an input event.
            new MutationObserver(() => {
                this.count = list.children.length;
                list.dispatchEvent(new Event('input', { bubbles: true }));
            }).observe(list, { childList: true });

            // Discarding resets the inputs, but only opening the page again puts back cards that were added, moved or deleted.
            const keys = () => [...list.children].map((card) => card.dataset.blockKey).join();
            const loaded = keys();
            const form = this.$root.closest('form');
            form?.addEventListener('reset', () => keys() !== loaded && location.assign(location.href));

            // SortableJS comes after the page's scripts, so wait for it. Without it (a blocked CDN) the buttons still move blocks.
            const sortable = () => typeof Sortable !== 'undefined' && Sortable.create(list, { handle: '[data-handle]', animation: 150 });
            document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', sortable) : sortable();
        },

        move(button, step) {
            const card = button.closest('[data-block]');
            const next = step < 0 ? card.previousElementSibling : card.nextElementSibling;

            if (next) {
                step < 0 ? next.before(card) : next.after(card);
            }
        },

        remove(button) {
            button.closest('[data-block]').remove();
        },

        duplicate(button) {
            if (this.full) {
                return;
            }

            const card = button.closest('[data-block]');
            const list = this.$refs.list;
            let key;

            do {
                key = 'b_' + [...crypto.getRandomValues(new Uint8Array(3))].map((byte) => byte.toString(16).padStart(2, '0')).join('');
            } while (list.querySelector('[data-block-key="' + key + '"]'));

            // The copy is made from the card's markup, which holds the values the page loaded with. Put what has been typed since into it.
            for (const field of card.querySelectorAll('input, textarea, select')) {
                if (field.tagName === 'TEXTAREA') {
                    field.textContent = field.value;
                } else if (field.tagName === 'SELECT') {
                    [...field.options].forEach((option) => option.toggleAttribute('selected', option.selected));
                } else if (field.type === 'checkbox' || field.type === 'radio') {
                    field.toggleAttribute('checked', field.checked);
                } else {
                    field.setAttribute('value', field.value);
                }
            }

            const template = document.createElement('template');
            template.innerHTML = card.outerHTML;
            const copy = template.content.firstElementChild;

            // The key is in the names and ids and what points to them, but not in the values, which are the editor's.
            for (const element of [copy, ...copy.querySelectorAll('*')]) {
                for (const attribute of element.attributes) {
                    if (attribute.name !== 'value') {
                        attribute.value = attribute.value.replaceAll(card.dataset.blockKey, key);
                    }
                }
            }

            copy.setAttribute('x-data', '{ open: true }');
            card.after(copy);
            window.htmx?.process(copy);
        },

        // Keeps the summary in a card's header as the first text field is typed into.
        summarize(event) {
            const card = event.target.closest?.('[data-block]');
            const handle = card?.dataset.summaryField;

            if (handle && event.target.name?.endsWith('[' + card.dataset.blockKey + '][' + handle + ']')) {
                card.querySelector('[data-summary]').textContent = event.target.value.slice(0, 80);
            }
        },
    });
</script>
