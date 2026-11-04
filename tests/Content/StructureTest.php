<?php

namespace Tests\Content;

use Bonfire\Content\Collection;
use Bonfire\Content\ContentException;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Content\ReindexResult;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\SubmitsForms;

/**
 * Structured collections: the tree file, the columns it gives the index, and the admin editor for it.
 *
 * @internal
 */
final class StructureTest extends ContentTestCase
{
    use SubmitsForms;

    private const HOME      = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1';
    private const ABOUT     = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa2';
    private const TEAM      = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa3';
    private const HISTORY   = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa4';
    private const JOBS      = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa5';
    private const TREE_FILE = 'trees/collections/pages.yaml';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        $this->writePages();
        $this->writeTree([
            ['id' => self::HOME],
            ['id' => self::ABOUT, 'children' => [
                ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]],
                ['id' => self::HISTORY],
            ]],
        ]);
        $this->reindex();

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    // Indexing

    public function testReindexWritesParentSortAndUriFromTheTree(): void
    {
        $this->assertSame([null, 0, '/home'], $this->placement(self::HOME));
        $this->assertSame([null, 1, '/about'], $this->placement(self::ABOUT));
        $this->assertSame([self::ABOUT, 0, '/about/team'], $this->placement(self::TEAM));
        $this->assertSame([self::ABOUT, 1, '/about/history'], $this->placement(self::HISTORY));
        $this->assertSame([self::TEAM, 0, '/about/team/jobs'], $this->placement(self::JOBS));
    }

    public function testASecondReindexWithNoChangesIsAllUnchanged(): void
    {
        $result = $this->reindex();

        $this->assertSame(0, $result->added + $result->updated + $result->removed);
        $this->assertSame(9, $result->unchanged);
    }

    public function testEntriesMissingFromTheTreeFileAreIndexedAtTheRoot(): void
    {
        $this->writeTree([['id' => self::ABOUT, 'children' => [['id' => self::TEAM]]]]);

        $result = $this->reindex();

        $this->assertSame([], $result->errors);
        $this->assertSame([null, 0, '/about'], $this->placement(self::ABOUT));
        $this->assertSame([self::ABOUT, 0, '/about/team'], $this->placement(self::TEAM));

        $roots = array_filter(
            [self::HOME, self::HISTORY, self::JOBS],
            fn (string $id): bool => $this->placement($id)[0] === null,
        );
        $this->assertCount(3, $roots);
        $this->assertSame([1, 2, 3], $this->sortedRootSorts([self::HOME, self::HISTORY, self::JOBS]));
        $this->assertSame('/history', $this->placement(self::HISTORY)[2]);
    }

    public function testWithoutATreeFileEveryEntryIsAtTheRoot(): void
    {
        unlink($this->site . self::TREE_FILE);

        $result = $this->reindex();

        $this->assertSame([], $result->errors);
        $this->seeNumRecords(5, 'content_entries', ['collection' => 'pages', 'parent_id' => null]);
        $this->assertSame('/team', $this->placement(self::TEAM)[2]);
    }

    public function testATreeNamingEntriesThatDontExistOrNamingOneTwiceIsTolerated(): void
    {
        $gone = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb1';
        $this->writeTree([
            ['id' => self::ABOUT, 'children' => [['id' => $gone, 'children' => [['id' => self::TEAM]]]]],
            ['id' => self::TEAM],
        ]);

        $result = $this->reindex();

        $this->assertSame([], $result->errors);
        // The missing parent's child moves up to where the parent was, and the second mention of an entry is ignored.
        $this->assertSame([self::ABOUT, 0, '/about/team'], $this->placement(self::TEAM));
    }

    public function testAMalformedTreeIsReportedAndEntriesStillGoToTheRoot(): void
    {
        file_put_contents($this->site . self::TREE_FILE, "- id: [\n");

        $result = $this->reindex();

        $this->assertArrayHasKey(self::TREE_FILE, $result->errors);
        $this->seeNumRecords(5, 'content_entries', ['collection' => 'pages', 'parent_id' => null]);
    }

    public function testRenamingAParentsSlugUpdatesEveryDescendantUri(): void
    {
        rename($this->site . 'collections/pages/about.md', $this->site . 'collections/pages/company.md');

        $result = $this->reindex();

        $this->assertSame([], $result->errors);
        $this->assertSame('/company', $this->placement(self::ABOUT)[2]);
        $this->assertSame('/company/team', $this->placement(self::TEAM)[2]);
        $this->assertSame('/company/history', $this->placement(self::HISTORY)[2]);
        $this->assertSame('/company/team/jobs', $this->placement(self::JOBS)[2]);
    }

    public function testTheRouteGivesTheUriItsPrefix(): void
    {
        $this->writePages("route: /docs/{slug}\n");

        $this->reindex();

        $this->assertSame('/docs/about/team', $this->placement(self::TEAM)[2]);
    }

    public function testWithoutARouteTheEntriesStillGetTheirPlaceButNoUri(): void
    {
        $this->writePages('');

        $this->reindex();

        $this->assertSame([self::ABOUT, 0, null], $this->placement(self::TEAM));
    }

    // Collection config

    #[DataProvider('provideABadStructureConfigIsRefused')]
    public function testABadStructureConfigIsRefused(string $config): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\n{$config}");

        $this->expectException(ContentException::class);
        Collection::load($this->site, 'pages');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideABadStructureConfigIsRefused(): iterable
    {
        yield 'structure is not a boolean' => ["structure: lots\n"];

        yield 'max_depth is zero' => ["structure: true\nmax_depth: 0\n"];

        yield 'max_depth is text' => ["structure: true\nmax_depth: deep\n"];
    }

    public function testTheConfigReadsStructureAndMaxDepth(): void
    {
        $this->writePages("max_depth: 3\n");

        $collection = Collection::load($this->site, 'pages');

        $this->assertTrue($collection->structure);
        $this->assertSame(3, $collection->maxDepth);
        $this->assertFalse(Collection::load($this->site, 'blog')->structure);
        $this->assertNull(Collection::load($this->site, 'blog')->maxDepth);
    }

    // Front end

    public function testANestedUriResolvesToItsEntry(): void
    {
        $this->assertSame(self::TEAM, content()->findByUri('/about/team')?->id);
        $this->assertSame(self::JOBS, content()->findByUri('about/team/jobs/')?->id);
        $this->assertNull(content()->findByUri('/team'));
    }

    // The tree editor

    public function testTheEditorNeedsContentStructure(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access', 'content.edit', 'content.publish');

        $this->actingAs($user)->get(route_to('content-structure', 'pages'))->assertRedirectTo(site_url(ADMIN_AREA));

        $before = $this->treeFile();
        $this->actingAs($user)->post(route_to('content-structure', 'pages'), ['tree' => '[]', 'hash' => hash('sha256', $before)])
            ->assertRedirectTo(site_url(ADMIN_AREA));
        $this->assertSame($before, $this->treeFile());
    }

    public function testACollectionWithoutAStructureHasNoEditor(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('content-structure', 'blog'));
    }

    public function testTheEditorShowsTheEntriesNestedAsTheTreeHasThem(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-structure', 'pages'));

        $response->assertOK();
        $xpath = $this->xpath($response->getBody());

        $this->assertSame(self::ABOUT, $xpath->query('//ul[@data-tree]/li[2]')->item(0)->getAttribute('data-id'));
        $this->assertSame(self::TEAM, $xpath->query('//ul[@data-tree]/li[2]/ul/li[1]')->item(0)->getAttribute('data-id'));
        $this->assertSame(self::JOBS, $xpath->query('//ul[@data-tree]/li[2]/ul/li[1]/ul/li[1]')->item(0)->getAttribute('data-id'));
        $this->assertSame(hash('sha256', $this->treeFile()), $this->formFields($response->getBody(), url_to('content-structure', 'pages'))['hash']);
    }

    public function testTheListLinksToTheEditorOnlyForThoseWhoCanUseIt(): void
    {
        $list = $this->actingAs($this->admin)->get(route_to('content-list', 'pages'));

        $this->assertCount(1, $this->xpath($list->getBody())->query('//a[@data-structure]'));

        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access', 'content.edit');
        $list = $this->actingAs($user)->get(route_to('content-list', 'pages'));

        $this->assertCount(0, $this->xpath($list->getBody())->query('//a[@data-structure]'));
    }

    public function testSavingReordersAndNestsAndUpdatesTheTreeFileAndTheIndex(): void
    {
        $response = $this->saveTree([
            ['id' => self::ABOUT, 'children' => [['id' => self::HISTORY]]],
            ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]],
            ['id' => self::HOME, 'children' => []],
        ]);

        $response->assertRedirectTo(site_url(route_to('content-structure', 'pages')));
        $this->assertSame([
            ['id' => self::ABOUT, 'children' => [['id' => self::HISTORY]]],
            ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]],
            ['id' => self::HOME],
        ], Yaml::parse($this->treeFile()));

        $this->assertSame([null, 0, '/about'], $this->placement(self::ABOUT));
        $this->assertSame([self::ABOUT, 0, '/about/history'], $this->placement(self::HISTORY));
        $this->assertSame([null, 1, '/team'], $this->placement(self::TEAM));
        $this->assertSame([self::TEAM, 0, '/team/jobs'], $this->placement(self::JOBS));
        $this->assertSame([null, 2, '/home'], $this->placement(self::HOME));
    }

    public function testASavedTreeIsWhatAReindexWouldBuild(): void
    {
        $this->saveTree([
            ['id' => self::JOBS],
            ['id' => self::HOME, 'children' => [['id' => self::ABOUT, 'children' => [['id' => self::TEAM], ['id' => self::HISTORY]]]]],
        ]);
        $saved = (new ContentEntryModel())->where('collection', 'pages')->orderBy('id')->findAll();

        $result = $this->reindex();

        $this->assertSame(9, $result->unchanged);
        $this->assertSame($saved, (new ContentEntryModel())->where('collection', 'pages')->orderBy('id')->findAll());
    }

    public function testATreeDeeperThanMaxDepthIsRefusedAndNothingChanges(): void
    {
        $this->writePages("route: /{slug}\nmax_depth: 2\n");
        $before = $this->treeFile();
        $this->reindex();

        // The depth counts the root as 1: Home > About > Team is 3.
        $response = $this->saveTree([
            ['id' => self::HOME, 'children' => [['id' => self::ABOUT, 'children' => [['id' => self::TEAM]]]]],
            ['id' => self::HISTORY],
            ['id' => self::JOBS],
        ], $before);

        $this->assertFalse($response->isRedirect());
        $this->assertSame($before, $this->treeFile());
        $this->assertSame('/about/team', $this->placement(self::TEAM)[2]);
    }

    public function testATreeExactlyAsDeepAsMaxDepthIsSaved(): void
    {
        $this->writePages("route: /{slug}\nmax_depth: 3\n");
        $this->reindex();

        $this->saveTree([
            ['id' => self::HOME, 'children' => [['id' => self::ABOUT, 'children' => [['id' => self::TEAM]]]]],
            ['id' => self::HISTORY],
            ['id' => self::JOBS],
        ])->assertRedirect();

        $this->assertSame('/home/about/team', $this->placement(self::TEAM)[2]);
    }

    public function testATreeThatChangedOnDiskSinceItWasOpenedIsRefused(): void
    {
        $opened = $this->treeFile();
        $edited = $opened . "# edited by hand\n";
        file_put_contents($this->site . self::TREE_FILE, $edited);

        $response = $this->saveTree([['id' => self::HOME]], $opened);

        $response->assertStatus(409);
        $this->assertSame($edited, $this->treeFile());
        $this->assertSame([self::ABOUT, 0, '/about/team'], $this->placement(self::TEAM));
    }

    public function testATreeFileThatDidntExistWhenOpenedCanBeCreatedButNotOverwrittenBehindTheEditorsBack(): void
    {
        unlink($this->site . self::TREE_FILE);
        $this->reindex();

        $this->saveTree([['id' => self::TEAM, 'children' => [['id' => self::HOME]]]], '')->assertRedirect();
        $this->assertFileExists($this->site . self::TREE_FILE);
        $this->assertSame('/team/home', $this->placement(self::HOME)[2]);

        $this->saveTree([['id' => self::HOME]], '')->assertStatus(409);
        $this->assertSame('/team/home', $this->placement(self::HOME)[2]);
    }

    public function testATreeWithAnEntryThatIsNotInTheCollectionIsRefused(): void
    {
        $before = $this->treeFile();

        $response = $this->saveTree([['id' => self::HOME], ['id' => '11111111-1111-4111-8111-111111111111']]);

        $this->assertFalse($response->isRedirect());
        $this->assertSame($before, $this->treeFile());
    }

    #[DataProvider('provideAMalformedPostedTreeIsRefused')]
    public function testAMalformedPostedTreeIsRefused(string $posted): void
    {
        $before = $this->treeFile();

        $response = $this->actingAs($this->admin)->post(route_to('content-structure', 'pages'), ['tree' => $posted, 'hash' => hash('sha256', $before)]);

        $this->assertFalse($response->isRedirect());
        $this->assertSame($before, $this->treeFile());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAMalformedPostedTreeIsRefused(): iterable
    {
        yield 'not json' => ['{'];

        yield 'not a list' => ['{"id":"aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1"}'];

        yield 'a node with no id' => ['[{"children":[]}]'];

        yield 'an id that is not a uuid' => ['[{"id":"home"}]'];

        yield 'children that is not a list' => ['[{"id":"aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1","children":"x"}]'];

        yield 'an entry twice' => ['[{"id":"aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1"},{"id":"aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1"}]'];
    }

    public function testATreeThatGivesAnEntryAnotherCollectionsUriIsRefused(): void
    {
        $this->reindexBlogWith("route: /about/{slug}\n");
        $extra = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb1';
        $this->writeFile('collections/pages/first-post.md', "---\nid: {$extra}\ntitle: Clash\n---\n");
        $this->reindex();
        $before = $this->treeFile();

        $response = $this->saveTree([
            ['id' => self::HOME],
            ['id' => self::ABOUT, 'children' => [['id' => $extra], ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]], ['id' => self::HISTORY]]],
        ]);

        $this->assertFalse($response->isRedirect());
        $this->assertSame($before, $this->treeFile());
        $this->assertSame('/first-post', $this->placement($extra)[2]);
    }

    public function testASavedTreeLeavesNoTemporaryFilesBehind(): void
    {
        $this->saveTree([['id' => self::HOME]]);

        $this->assertSame(['pages.yaml'], array_values(array_diff(scandir($this->site . 'trees/collections'), ['.', '..'])));
    }

    // New and edited entries

    public function testANewEntryIsAppendedToTheRootOfTheTree(): void
    {
        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'pages'), [
            'blueprint' => 'page',
            'fields'    => ['title' => 'Contact', 'body' => 'Hi'],
            'status'    => 'published',
        ]);

        $row = (new ContentEntryModel())->where('slug', 'contact')->first();
        $this->assertNotNull($row);
        $response->assertRedirectTo(site_url(route_to('content-edit', 'pages', $row['id'])));

        $this->assertSame([null, 2, '/contact'], $this->placement($row['id']));

        $tree = Yaml::parse($this->treeFile());
        $this->assertSame(['id' => $row['id']], end($tree));
        $this->assertCount(3, $tree);
        $this->assertSame(10, $this->reindex()->unchanged);
    }

    public function testANewEntryWhenThereIsNoTreeFileCreatesOneWithEveryEntry(): void
    {
        unlink($this->site . self::TREE_FILE);
        $this->reindex();

        $this->actingAs($this->admin)->post(route_to('content-new', 'pages'), [
            'blueprint' => 'page',
            'fields'    => ['title' => 'Contact'],
        ]);

        $tree = Yaml::parse($this->treeFile());
        $this->assertCount(6, $tree);
        $this->assertSame('/contact', (new ContentEntryModel())->where('slug', 'contact')->first()['uri']);
    }

    public function testEditingAnEntryKeepsItsPlaceInTheTree(): void
    {
        $edit                      = $this->actingAs($this->admin)->get(route_to('content-edit', 'pages', self::TEAM));
        $fields                    = $this->formFields($edit->getBody(), url_to('content-edit', 'pages', self::TEAM));
        $fields['fields']['title'] = 'The team';

        $this->actingAs($this->admin)->post(route_to('content-edit', 'pages', self::TEAM), $fields)->assertRedirect();

        $this->assertSame('The team', (new ContentEntryModel())->find(self::TEAM)['title']);
        $this->assertSame([self::ABOUT, 0, '/about/team'], $this->placement(self::TEAM));
    }

    // Helpers

    /**
     * @param list<array<string, mixed>> $tree
     */
    private function writeTree(array $tree): void
    {
        if (! is_dir($this->site . 'trees/collections')) {
            mkdir($this->site . 'trees/collections', 0775, true);
        }

        file_put_contents($this->site . self::TREE_FILE, Yaml::dump($tree, 10, 2));
    }

    private function writePages(string $extra = "route: /{slug}\n"): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\nstructure: true\nblueprints:\n  - page\n{$extra}");
        @mkdir($this->site . 'collections/pages');
        @mkdir($this->blueprints . 'collections/pages');
        file_put_contents($this->blueprints . 'collections/pages/page.yaml', "title: Page\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n  - handle: body\n    type: markdown\n");

        foreach ([self::HOME => 'home', self::ABOUT => 'about', self::TEAM => 'team', self::HISTORY => 'history', self::JOBS => 'jobs'] as $id => $slug) {
            $this->writeFile("collections/pages/{$slug}.md", "---\nid: {$id}\ntitle: " . ucfirst($slug) . "\n---\nBody of {$slug}.\n");
        }
    }

    private function reindex(): ReindexResult
    {
        return (new Indexer($this->site))->reindex();
    }

    /**
     * @return array{string|null, int|null, string|null} parent_id, sort and uri
     */
    private function placement(string $id): array
    {
        $row = (new ContentEntryModel())->find($id);

        return [$row['parent_id'], $row['sort'] === null ? null : (int) $row['sort'], $row['uri']];
    }

    /**
     * @param list<string> $ids
     *
     * @return list<int>
     */
    private function sortedRootSorts(array $ids): array
    {
        $sorts = array_map(fn (string $id): int => (int) $this->placement($id)[1], $ids);
        sort($sorts);

        return $sorts;
    }

    private function treeFile(): string
    {
        return (string) file_get_contents($this->site . self::TREE_FILE);
    }

    /**
     * Posts a tree the way the editor does.
     *
     * @param list<array<string, mixed>> $tree
     */
    private function saveTree(array $tree, ?string $openedOn = null): TestResponse
    {
        return $this->actingAs($this->admin)->post(route_to('content-structure', 'pages'), [
            'tree' => json_encode($tree),
            'hash' => hash('sha256', $openedOn ?? $this->treeFile()),
        ]);
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }
}
