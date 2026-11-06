<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\Collection;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\Fields\TermsField;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentTermModel;
use Bonfire\Content\Taxonomy;
use Bonfire\Content\TermStore;
use Bonfire\Fields\Field;
use CodeIgniter\I18n\Time;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class TaxonomyTest extends ContentTestCase
{
    private const PHP     = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private const LARAVEL = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
    private const FIRST   = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-06-01 12:00:00');
        $this->useTags();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    // Config

    public function testACollectionOptsInToTaxonomies(): void
    {
        $this->assertSame(['tags'], Collection::load($this->site, 'blog')->taxonomies);

        $this->writeFile('collections/blog.yaml', "title: Blog\n");

        $this->assertSame([], Collection::load($this->site, 'blog')->taxonomies);
    }

    public function testACollectionCantUseATaxonomyNamedAfterAKeyEntriesUse(): void
    {
        $this->writeFile('collections/blog.yaml', "title: Blog\ntaxonomies:\n  - status\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('status');
        Collection::load($this->site, 'blog');
    }

    public function testATaxonomyHasATitleAndAnOptionalRouteAndTemplate(): void
    {
        $this->writeFile('taxonomies/tags.yaml', "title: Tags\nroute: /tags/{slug}\ntemplate: tags/show\n");

        $taxonomy = Taxonomy::load($this->site, 'tags');

        $this->assertSame('Tags', $taxonomy->title);
        $this->assertSame('php', $taxonomy->slugAt('/tags/php/'));
        $this->assertNull($taxonomy->slugAt('/tags'));
        $this->assertNull($taxonomy->slugAt('/other/php'));
        $this->assertNull($taxonomy->slugAt('/tags/a/b'));
    }

    public function testATaxonomyRouteNeedsASlugPlaceholder(): void
    {
        $this->writeFile('taxonomies/tags.yaml', "route: /tags\n");

        $this->expectException(ContentException::class);
        Taxonomy::load($this->site, 'tags');
    }

    public function testAnUnknownTaxonomyIsRefused(): void
    {
        $this->expectException(ContentException::class);
        Taxonomy::load($this->site, '../collections/blog');
    }

    // The terms field

    public function testAFieldOfTypeTermsNeedsATaxonomy(): void
    {
        file_put_contents($this->blueprints . 'collections/blog/bad.yaml', "fields:\n  - handle: tags\n    type: terms\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('taxonomy');
        Blueprint::load($this->blueprints, 'blog', 'bad');
    }

    public function testATermsFieldCantSaveOverTheKeysAnEntryUses(): void
    {
        foreach (['status', 'id', 'title', 'body'] as $taxonomy) {
            file_put_contents($this->blueprints . 'collections/blog/bad.yaml', "fields:\n  - handle: labels\n    type: terms\n    taxonomy: {$taxonomy}\n");

            try {
                Blueprint::load($this->blueprints, 'blog', 'bad');
                $this->fail("A terms field for {$taxonomy} was accepted.");
            } catch (ContentException $e) {
                $this->assertStringContainsString("`{$taxonomy}`", $e->getMessage());
            }
        }
    }

    public function testTwoFieldsCantSaveToTheSameKey(): void
    {
        file_put_contents($this->blueprints . 'collections/blog/bad.yaml', "fields:\n  - handle: tags\n    type: text\n  - handle: labels\n    type: terms\n    taxonomy: tags\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('both save to `tags`');
        Blueprint::load($this->blueprints, 'blog', 'bad');
    }

    public function testATermBlueprintCantHaveABody(): void
    {
        mkdir($this->blueprints . 'taxonomies');
        file_put_contents($this->blueprints . 'taxonomies/tags.yaml', "fields:\n  - handle: title\n    type: text\n  - handle: body\n    type: markdown\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('body');
        Blueprint::loadTerm($this->blueprints, 'tags');
    }

    public function testATermStoreWontWriteOutsideItsTaxonomy(): void
    {
        $this->expectException(ContentException::class);

        try {
            (new TermStore($this->site))->create(Taxonomy::load($this->site, 'tags'), '../../collections/blog/x', ['title' => 'Escape']);
        } finally {
            $this->assertFileDoesNotExist($this->site . 'collections/blog/x.yaml');
            $this->assertFileDoesNotExist($this->site . 'taxonomies/tags/../../collections/blog/x.yaml');
        }
    }

    public function testTheTermsFieldKeepsSlugsAsAListUnderTheTaxonomysHandle(): void
    {
        $type  = new TermsField();
        $field = new Field('labels', 'terms', 'Labels', '', null, ['taxonomy' => 'tags']);

        $this->assertSame('tags', $type->key($field));
        $this->assertSame('php,laravel', $type->prepare(['php', ' laravel ', 'php', 'bad slug', '', 5], $field, []));
        $this->assertSame('php,laravel', $type->prepare('php, laravel', $field, []));
        $this->assertSame('', $type->prepare(null, $field, []));
        $this->assertSame(['php', 'laravel'], $type->toStored('php,laravel', $field));
        $this->assertSame([], $type->toStored('', $field));
        $this->assertSame('php,laravel', $type->fromStored(['php', 'laravel'], $field));
        $this->assertSame('', $type->fromStored(null, $field));
    }

    // Reindex

    public function testReindexFillsTheTermsAndThePivot(): void
    {
        $result = (new Indexer($this->site))->reindex();

        $this->assertSame([], $result->errors);
        $this->assertSame(
            ['laravel' => self::LARAVEL, 'php' => self::PHP],
            array_column((new ContentTermModel())->orderBy('slug')->findAll(), 'id', 'slug'),
        );

        $term = (new ContentTermModel())->find(self::PHP);
        $this->assertSame(['tags', 'PHP', 'taxonomies/tags/php.yaml'], [$term['taxonomy'], $term['title'], $term['path']]);
        $this->assertSame(['id' => self::PHP, 'title' => 'PHP', 'color' => 'blue'], json_decode($term['data'], true));

        $this->assertSame(
            [
                self::FIRST . ' ' . self::PHP,
                self::FIRST . ' ' . self::LARAVEL,
                '22222222-2222-4222-8222-222222222222 ' . self::PHP,
                '33333333-3333-4333-8333-333333333333 ' . self::PHP,
                '44444444-4444-4444-8444-444444444444 ' . self::PHP,
            ],
            $this->pivot(),
        );
    }

    public function testReindexRebuildsThePivotFromFilesAlone(): void
    {
        (new Indexer($this->site))->reindex();
        $before = $this->pivot();

        db_connect()->table('content_entry_terms')->emptyTable();
        db_connect()->table('content_terms')->emptyTable();
        $this->assertSame([], $this->pivot());

        $result = (new Indexer($this->site))->reindex();

        $this->assertSame([], $result->errors);
        $this->assertSame($before, $this->pivot());

        $result = (new Indexer($this->site))->reindex(true);

        $this->assertSame([], $result->errors);
        $this->assertSame($before, $this->pivot());
        $this->assertSame(2, (new ContentTermModel())->countAllResults());
    }

    public function testReindexFollowsEditsToTheFiles(): void
    {
        (new Indexer($this->site))->reindex();

        unlink($this->site . 'taxonomies/tags/laravel.yaml');
        $this->writeFile('collections/blog/2026-02-15.second-post.md', "---\nid: 22222222-2222-4222-8222-222222222222\ntags: []\n---\nBody.\n");
        (new Indexer($this->site))->reindex();

        $this->assertSame(['php'], array_column((new ContentTermModel())->findAll(), 'slug'));
        $this->assertSame(
            [self::FIRST . ' ' . self::PHP, '33333333-3333-4333-8333-333333333333 ' . self::PHP, '44444444-4444-4444-8444-444444444444 ' . self::PHP],
            $this->pivot(),
        );
    }

    public function testReindexLeavesOutTagsNoTermHas(): void
    {
        $this->writeFile('collections/blog/2026-02-15.second-post.md', "---\nid: 22222222-2222-4222-8222-222222222222\ntags: [php, ghost]\n---\nBody.\n");

        $result = (new Indexer($this->site))->reindex();

        $this->assertSame([], $result->errors);
        $this->assertContains('22222222-2222-4222-8222-222222222222 ' . self::PHP, $this->pivot());
        $this->assertCount(5, $this->pivot());
    }

    public function testATermIsOnlyIndexedForCollectionsThatUseItsTaxonomy(): void
    {
        $this->writeFile('collections/blog.yaml', "title: Blog\ndated: true\n");

        (new Indexer($this->site))->reindex();

        $this->assertSame([], $this->pivot());
        $this->assertSame(2, (new ContentTermModel())->countAllResults());
    }

    public function testATermFileThatCantBeIndexedIsReportedAndLeftOut(): void
    {
        $this->writeFile('taxonomies/tags/broken.yaml', "title: No id\n");
        $this->writeFile('taxonomies/tags/zdupe.yaml', 'id: ' . self::PHP . "\ntitle: Same id\n");
        $this->writeFile('taxonomies/tags/notes.txt', 'ignored');

        $result = (new Indexer($this->site))->reindex();

        // The first file with an id wins.
        $this->assertSame(['taxonomies/tags/broken.yaml', 'taxonomies/tags/zdupe.yaml'], array_keys($result->errors));
        $this->assertSame(2, (new ContentTermModel())->countAllResults());
    }

    public function testASiteWithoutTaxonomiesReindexesAsBefore(): void
    {
        delete_files($this->site . 'taxonomies', true, false, true);
        rmdir($this->site . 'taxonomies');

        $result = (new Indexer($this->site))->reindex();

        $this->assertSame([], $result->errors);
        $this->assertSame(4, $result->added);
    }

    // Queries

    public function testWhereTermReturnsOnlyMatchingPublishedEntries(): void
    {
        (new Indexer($this->site))->reindex();

        $php = content()->collection('blog')->published()->whereTerm('tags', 'php')->get();

        $this->assertSame(['second-post', 'first-post'], array_map(static fn (Entry $entry): string => $entry->slug, $php));

        $laravel = content()->collection('blog')->published()->whereTerm('tags', 'laravel')->get();

        $this->assertSame(['first-post'], array_map(static fn (Entry $entry): string => $entry->slug, $laravel));
    }

    public function testWhereTermAloneLeavesOutDraftsAndFutureEntries(): void
    {
        (new Indexer($this->site))->reindex();

        $entries = content()->collection('blog')->whereTerm('tags', 'php')->get();

        $this->assertSame(['second-post', 'first-post'], array_map(static fn (Entry $entry): string => $entry->slug, $entries));
    }

    public function testWhereTermAndsRepeatedTermsAndMatchesNothingForAnUnknownTerm(): void
    {
        (new Indexer($this->site))->reindex();

        $this->assertCount(1, content()->collection('blog')->whereTerm('tags', 'php')->whereTerm('tags', 'laravel')->get());
        $this->assertSame([], content()->collection('blog')->whereTerm('tags', 'ghost')->get());
        $this->assertCount(1, content()->collection('blog')->published()->whereTerm('tags', 'php')->paginate(1));
    }

    public function testWhereTermNeedsATaxonomyTheCollectionUses(): void
    {
        $this->expectException(ContentException::class);
        content()->collection('blog')->whereTerm('categories', 'news');
    }

    public function testTermLookupsCountPublishedEntries(): void
    {
        (new Indexer($this->site))->reindex();

        $terms = content()->terms('tags');

        $this->assertSame(['laravel', 'php'], array_map(static fn ($term): string => $term->slug, $terms));
        $this->assertSame([1, 2], array_map(static fn ($term): ?int => $term->count, $terms));
        $this->assertSame('PHP', $terms[1]->title);
        $this->assertSame('blue', $terms[1]->field('color'));

        $php = content()->term('tags', 'php');

        $this->assertSame([self::PHP, 2], [$php?->id, $php?->count]);
        $this->assertNull(content()->term('tags', 'ghost'));
    }

    public function testATermHasNoCountForADraftOnlyTag(): void
    {
        $this->writeFile('taxonomies/tags/drafty.yaml', "id: cccccccc-cccc-4ccc-8ccc-cccccccccccc\ntitle: Drafty\n");
        $this->writeFile('collections/blog/2026-03-01.draft-post.md', "---\nid: 33333333-3333-4333-8333-333333333333\nstatus: draft\ntags: [drafty]\n---\nBody.\n");
        (new Indexer($this->site))->reindex();

        $this->assertSame(0, content()->term('tags', 'drafty')?->count);
    }

    public function testATermListsItsPublishedEntries(): void
    {
        (new Indexer($this->site))->reindex();

        $entries = content()->term('tags', 'php')?->entries() ?? [];

        $this->assertSame(['second-post', 'first-post'], array_map(static fn (Entry $entry): string => $entry->slug, $entries));
    }

    public function testAnUnknownTaxonomyCantBeQueriedForTerms(): void
    {
        $this->expectException(ContentException::class);
        content()->terms('nope');
    }

    /**
     * @return list<string> "entry_id term_id" of each pivot row, sorted
     */
    private function pivot(): array
    {
        $pairs = array_map(
            static fn (array $row): string => $row['entry_id'] . ' ' . $row['term_id'],
            db_connect()->table('content_entry_terms')->get()->getResultArray(),
        );
        sort($pairs);

        return $pairs;
    }
}
