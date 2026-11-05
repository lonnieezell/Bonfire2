<?php

namespace Tests\Support\Content;

use Bonfire\Content\Entry;
use CodeIgniter\Controller;

/**
 * A developer's controller that puts the entry's SEO values in the head itself.
 */
class SeoController extends Controller
{
    public function show(Entry $entry): string
    {
        content_seo($entry);

        return $this->bare($entry);
    }

    /**
     * Renders the page without asking for the SEO values.
     */
    public function bare(Entry $entry): string
    {
        return render('App', 'blog/seo', ['entry' => $entry]);
    }
}
