<script>
    // Makes a list and those inside it draggable. Without SortableJS (a blocked CDN) they can't be moved, but a save still posts them as they are.
    const bfTreeSortable = (root) => {
        if (typeof Sortable === 'undefined') {
            return;
        }

        for (const list of [root, ...root.querySelectorAll('[data-children]')]) {
            Sortable.create(list, {
                group: 'bf-content-tree',
                handle: '[data-handle]',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onEnd: () => list.dispatchEvent(new Event('input', { bubbles: true })),
            });
        }
    };
</script>
