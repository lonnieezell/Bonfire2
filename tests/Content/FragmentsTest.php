<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\Collection;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\EntryForm;
use Bonfire\Content\EntryStore;
use Bonfire\Content\Fields\BlocksField;
use Bonfire\Content\Indexer;
use Tests\Support\Content\ContentTestCase;

/**
 * Fragments: entries of a collection with `fragments: true`, reused on many pages.
 *
 * @internal
 */
final class FragmentsTest extends ContentTestCase
{
    private const SALE  = 'f0000000-0000-4000-8000-000000000001';
    private const OTHER = 'f0000000-0000-4000-8000-000000000002';
    private const FIRST = '11111111-1111-4111-8111-111111111111';

    // Config

    public function testACollectionIsNotAFragmentCollectionByDefault(): void
    {
        $this->assertFalse(Collection::load($this->site, 'blog')->fragments);
    }

    public function testFragmentsIsReadFromTheConfig(): void
    {
        $this->writeFile('collections/promos.yaml', "title: Promos\nfragments: true\n");

        $this->assertTrue(Collection::load($this->site, 'promos')->fragments);
    }

    public function testAFragmentCollectionCannotHaveARoute(): void
    {
        $this->writeFile('collections/promos.yaml', "title: Promos\nfragments: true\nroute: /promos/{slug}\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('`fragments: true` can\'t have a `route`');

        Collection::load($this->site, 'promos');
    }

    public function testFragmentsMustBeTrueOrFalse(): void
    {
        $this->writeFile('collections/promos.yaml', "title: Promos\nfragments: yes please\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('`fragments` must be true or false');

        Collection::load($this->site, 'promos');
    }

    // content()->fragment()

    public function testFragmentReturnsAPublishedFragment(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'spring-sale', 'Spring sale');

        $fragment = content()->fragment('spring-sale');

        $this->assertInstanceOf(Entry::class, $fragment);
        $this->assertSame(self::SALE, $fragment->id);
        $this->assertSame([], (new Indexer($this->site))->reindex()->errors);
    }

    public function testFragmentIsNullForADraftAScheduledOrAMissingFragment(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'draft-one', 'Draft', "status: draft\n");
        $this->writeFragment('promos', self::OTHER, 'later-one', 'Later', "published_at: 2099-01-01 00:00:00\n");

        $this->assertNull(content()->fragment('draft-one'));
        $this->assertNull(content()->fragment('later-one'));
        $this->assertNull(content()->fragment('nothing-here'));
    }

    public function testFragmentIsNullOnceTheEntryIsTrashed(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'spring-sale', 'Spring sale');
        $this->assertInstanceOf(Entry::class, content()->fragment('spring-sale'));

        (new EntryStore($this->site))->trash(Collection::load($this->site, 'promos'), self::SALE, 0);

        $this->assertNull(content()->fragment('spring-sale'));
    }

    public function testFragmentOnlyLooksInFragmentCollections(): void
    {
        $this->useFragmentCollection('promos');

        $this->assertNull(content()->fragment('first-post'));
        $this->assertNull(content()->fragment('blog/first-post'));
        $this->assertNull(content()->fragment('nope/first-post'));
    }

    public function testFragmentIsNullWithoutAnyFragmentCollection(): void
    {
        $this->assertNull(content()->fragment('first-post'));
    }

    public function testACollectionPrefixNamesTheCollection(): void
    {
        $this->useFragmentCollection('promos');
        $this->useFragmentCollection('banners');
        $this->writeFragment('promos', self::SALE, 'spring-sale', 'Promo');
        $this->writeFragment('banners', self::OTHER, 'spring-sale', 'Banner');

        $this->assertSame('Promo', content()->fragment('promos/spring-sale')?->title);
        $this->assertSame('Banner', content()->fragment('banners/spring-sale')?->title);
    }

    public function testABareSlugFindsTheOnlyFragmentWithItAcrossCollections(): void
    {
        $this->useFragmentCollection('promos');
        $this->useFragmentCollection('banners');
        $this->writeFragment('banners', self::OTHER, 'top-bar', 'Banner');

        $this->assertSame(self::OTHER, content()->fragment('top-bar')?->id);
    }

    public function testABareSlugInTwoCollectionsIsNullAndLogged(): void
    {
        $this->useFragmentCollection('promos');
        $this->useFragmentCollection('banners');
        $this->writeFragment('promos', self::SALE, 'spring-sale', 'Promo');
        $this->writeFragment('banners', self::OTHER, 'spring-sale', 'Banner');

        $this->assertNull(content()->fragment('spring-sale'));
        $this->assertLogged('error', 'Content: more than one fragment has the slug spring-sale, so none was returned. Name its collection too, as in collection/slug.');
    }

    // The fragment block

    public function testTheBlockReturnsTheFragmentItLinksTo(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'spring-sale', 'Spring sale');
        $this->writePage([self::SALE]);

        $this->assertSame(self::SALE, $this->page()->block('sections')?->fragment()?->id);
    }

    public function testTheBlockIsNullForADraftATrashedOrAMissingFragment(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'draft-one', 'Draft', "status: draft\n");
        $this->writePage([self::SALE]);
        $this->assertNull($this->page()->block('sections')?->fragment());

        $this->writeFragment('promos', self::OTHER, 'gone', 'Gone');
        (new EntryStore($this->site))->trash(Collection::load($this->site, 'promos'), self::OTHER, 0);
        $this->writePage([self::OTHER]);
        $this->assertNull($this->page()->block('sections')?->fragment());

        $this->writePage(['f0000000-0000-4000-8000-00000000dead']);
        $this->assertNull($this->page()->block('sections')?->fragment());
    }

    public function testTheBlockNeverReturnsAnEntryOutsideAFragmentCollection(): void
    {
        $this->useFragmentCollection('promos');
        $this->writePage([self::FIRST]);

        $this->assertNull($this->page()->block('sections')?->fragment());
    }

    public function testABlockOfAnotherTypeHasNoFragment(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'spring-sale', 'Spring sale');
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: " . self::FIRST . "\ntitle: First post\nsections:\n  - type: hero\n    fragment: [" . self::SALE . "]\n---\nBody.\n");
        (new Indexer($this->site))->reindex();

        $this->assertNull($this->page()->block('sections')?->fragment());
    }

    public function testAFieldThatNamesNoTypesOffersTheBuiltInBlockOnlyWithAFragmentCollection(): void
    {
        $this->useBlocksField();
        mkdir($this->blueprints . 'blocks');
        file_put_contents($this->blueprints . 'blocks/hero.yaml', "title: Hero\nfields:\n  - handle: heading\n    type: text\n");

        $this->assertSame(['hero'], BlocksField::types($this->blueprint()->field('sections')));

        $this->useFragmentCollection('promos');

        $this->assertSame(['fragment', 'hero'], BlocksField::types($this->blueprint()->field('sections')));
    }

    public function testTheBlockCannotBeUsedWithoutAFragmentCollection(): void
    {
        $this->useBlocksField("    blocks: [fragment]\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('`fragments: true`');

        $this->blueprint();
    }

    public function testAFileOfTheSameNameReplacesTheBuiltInBlock(): void
    {
        mkdir($this->blueprints . 'blocks');
        file_put_contents($this->blueprints . 'blocks/fragment.yaml', "title: Mine\nfields:\n  - handle: heading\n    type: text\n");

        $this->assertSame('Mine', Blueprint::loadBlock($this->blueprints, 'fragment')->title);
    }

    public function testTheBlockSavesALinkToAFragment(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'spring-sale', 'Spring sale');
        $this->useBlocksField("    blocks: [fragment]\n");
        $form   = new EntryForm($this->blueprint(), true);
        $values = $form->prepare($this->posting([self::SALE]));

        $this->assertSame([], $form->validate($values));
        $this->assertSame([['type' => 'fragment', 'fragment' => [self::SALE]]], $form->apply($values, [], '')[0]['sections']);
    }

    public function testTheBlockRefusesALinkOutsideTheFragmentCollections(): void
    {
        $this->useFragmentCollection('promos');
        $this->useBlocksField("    blocks: [fragment]\n");
        $form = new EntryForm($this->blueprint(), true);

        $errors = $form->validate($form->prepare($this->posting([self::FIRST])));

        $this->assertSame(lang('Content.entriesInvalid'), $errors['sections.1.fragment']);
    }

    public function testTheBlockRefusesMoreThanOneLink(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'spring-sale', 'Spring sale');
        $this->writeFragment('promos', self::OTHER, 'autumn-sale', 'Autumn sale');
        $this->useBlocksField("    blocks: [fragment]\n");
        $form = new EntryForm($this->blueprint(), true);

        $errors = $form->validate($form->prepare($this->posting([self::SALE, self::OTHER])));

        $this->assertSame(lang('Content.entriesTooMany', [1]), $errors['sections.1.fragment']);
    }

    // Rendering

    public function testAFragmentRendersThroughTheDevelopersPartial(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'spring-sale', 'Spring sale');
        $this->writePage([self::SALE]);

        $this->assertSame(['spring-sale'], $this->renderedFragments());
    }

    public function testACycleRendersNothingAndIsLogged(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'a', 'A', $this->sections([self::OTHER]));
        $this->writeFragment('promos', self::OTHER, 'b', 'B', $this->sections([self::SALE]));
        $this->writePage([self::SALE]);

        $this->assertSame(['a', 'b'], $this->renderedFragments());
        $this->assertLogged('error', 'Content: fragment ' . self::SALE . ' was not rendered, because it embeds itself.');
    }

    public function testACycleIsCaughtWhenAFragmentIsRenderedDirectly(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'a', 'A', $this->sections([self::OTHER]));
        $this->writeFragment('promos', self::OTHER, 'b', 'B', $this->sections([self::SALE]));

        preg_match_all('/data-fragment="([\w-]+)"/', (string) content()->fragment('a')?->renderBlocks('sections'), $matches);

        $this->assertSame(['b'], $matches[1]);
        $this->assertLogged('error', 'Content: fragment ' . self::SALE . ' was not rendered, because it embeds itself.');
    }

    public function testAFragmentThatLinksToItselfRendersNothing(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'a', 'A', $this->sections([self::SALE]));
        $this->writePage([self::SALE]);

        $this->assertSame(['a'], $this->renderedFragments());
    }

    public function testADepthOverFiveRendersNothingAndIsLogged(): void
    {
        $this->useFragmentCollection('promos');
        $ids = array_map(static fn (int $n): string => 'f0000000-0000-4000-8000-00000000010' . $n, range(1, 6));

        foreach ($ids as $index => $id) {
            $this->writeFragment('promos', $id, 'level-' . ($index + 1), 'Level', isset($ids[$index + 1]) ? $this->sections([$ids[$index + 1]]) : '');
        }

        $this->writePage([$ids[0]]);

        $this->assertSame(['level-1', 'level-2', 'level-3', 'level-4', 'level-5'], $this->renderedFragments());
        $this->assertLogged('error', 'Content: fragment ' . $ids[5] . ' was not rendered, because fragments are nested more than 5 deep.');
    }

    public function testTheSameFragmentTwiceOnAPageIsNotACycle(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'a', 'A');
        $this->writePage([self::SALE], [self::SALE]);

        $this->assertSame(['a', 'a'], $this->renderedFragments());
    }

    public function testAnErrorWhileRenderingLeavesNothingOnTheStack(): void
    {
        $this->useFragmentCollection('promos');
        $this->writeFragment('promos', self::SALE, 'a', 'A', "sections:\n  - type: quote\n");
        $this->writePage([self::SALE]);

        $this->assertTrue($this->renderingThrows());
        // A fragment left on the stack would be taken for a cycle, and render nothing instead of throwing.
        $this->assertTrue($this->renderingThrows());
    }

    /**
     * Whether rendering the page throws. The exception leaves the partials' output buffers open, so this closes them.
     */
    private function renderingThrows(): bool
    {
        $level = ob_get_level();

        try {
            $this->page()->renderBlocks('sections');

            return false;
        } catch (ContentException) {
            return true;
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }

    /**
     * Gives the post blueprint a `sections` blocks field.
     *
     * @param string $options more lines for the field, indented four spaces
     */
    private function useBlocksField(string $options = ''): void
    {
        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n  - handle: sections\n    type: blocks\n{$options}",
        );
    }

    private function blueprint(): Blueprint
    {
        return Blueprint::load($this->blueprints, 'blog', 'post');
    }

    /**
     * @param list<string> $ids
     *
     * @return array<string, mixed>
     */
    private function posting(array $ids): array
    {
        return ['fields' => ['title' => 'T', 'sections' => [['type' => 'fragment', 'fragment' => $ids]]]];
    }

    /**
     * Makes the first post a page of one fragment block for each list of ids, and reindexes.
     *
     * @param list<string> ...$links
     */
    private function writePage(array ...$links): void
    {
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: " . self::FIRST . "\ntitle: First post\n" . $this->sections(...$links) . "---\nBody.\n");
        (new Indexer($this->site))->reindex();
    }

    /**
     * Front matter for a `sections` field of one fragment block for each list of ids.
     *
     * @param list<string> ...$links
     */
    private function sections(array ...$links): string
    {
        $yaml = "sections:\n";

        foreach ($links as $ids) {
            $yaml .= "  - type: fragment\n    fragment: [" . implode(', ', $ids) . "]\n";
        }

        return $yaml;
    }

    /**
     * The slugs of the fragments the page renders, in order.
     *
     * @return list<string>
     */
    private function renderedFragments(): array
    {
        preg_match_all('/data-fragment="([\w-]+)"/', $this->page()->renderBlocks('sections'), $matches);

        return $matches[1];
    }

    private function page(): Entry
    {
        $entry = content()->find(self::FIRST);
        $this->assertInstanceOf(Entry::class, $entry);

        return $entry;
    }

    /**
     * Gives the site an empty collection with `fragments: true`.
     */
    private function useFragmentCollection(string $handle): void
    {
        $this->writeFile("collections/{$handle}.yaml", 'title: ' . ucfirst($handle) . "\nfragments: true\n");
        mkdir($this->site . "collections/{$handle}");
    }

    /**
     * Writes a fragment and reindexes.
     *
     * @param string $frontMatter more front matter lines
     */
    private function writeFragment(string $collection, string $id, string $slug, string $title, string $frontMatter = ''): void
    {
        $this->writeFile("collections/{$collection}/{$slug}.md", "---\nid: {$id}\ntitle: {$title}\n{$frontMatter}---\nThe body of {$title}.\n");
        (new Indexer($this->site))->reindex();
    }
}
