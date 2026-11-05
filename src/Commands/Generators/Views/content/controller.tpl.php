<@php

namespace <?= $namespace ?>;

use Bonfire\Content\Entry;

class <?= $class ?> extends BaseController
{
    /**
     * Lists the collection's published entries, a page at a time.
     */
    public function index(): string
    {
        $query = content()->collection('<?= $handle ?>')->published();

        return render('App', '<?= $handle ?>/index', [
            'entries' => $query->paginate(10),
            'pager'   => $query->pager(),
        ]);
    }

    /**
     * Shows a published entry. The collection hands it over with `controller: <?= $namespace ?>\<?= $class ?>::show`.
     */
    public function show(Entry $entry): string
    {
        content_seo($entry);

        return render('App', '<?= $handle ?>/show', ['entry' => $entry]);
    }
}
