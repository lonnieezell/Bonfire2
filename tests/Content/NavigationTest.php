<?php

namespace Tests\Content;

use Bonfire\Content\ContentException;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Content\Navigation;
use Bonfire\Content\NavigationTree;
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
 * Navigation menus: the config and tree files, the editor in the admin and navigation() on the front end.
 *
 * @internal
 */
final class NavigationTest extends ContentTestCase
{
    use SubmitsForms;

    private const FIRST     = '11111111-1111-4111-8111-111111111111';
    private const SECOND    = '22222222-2222-4222-8222-222222222222';
    private const DRAFT     = '33333333-3333-4333-8333-333333333333';
    private const FUTURE    = '44444444-4444-4444-8444-444444444444';
    private const TREE_FILE = 'trees/navigation/main.yaml';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        $this->reindexBlogWith("route: /blog/{slug}\n");
        $this->writeNavigation("title: Main menu\n");

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

    // The front end

    public function testNavigationReturnsTheTreeWithTitlesAndUrlsFromTheEntries(): void
    {
        $this->writeTree([
            ['entry' => self::FIRST, 'children' => [
                ['entry' => self::SECOND, 'title' => 'Next up'],
            ]],
            ['url' => 'https://example.com/docs', 'title' => 'Docs'],
        ]);

        $this->assertSame([
            [
                'title'    => 'First post',
                'url'      => '/blog/first-post',
                'active'   => false,
                'children' => [
                    ['title' => 'Next up', 'url' => '/blog/second-post', 'active' => false, 'children' => []],
                ],
            ],
            ['title' => 'Docs', 'url' => 'https://example.com/docs', 'active' => false, 'children' => []],
        ], content()->navigation('main', '/'));
    }

    public function testNavigationMarksTheItemOfTheCurrentUrlActive(): void
    {
        $this->writeTree([
            ['entry' => self::FIRST],
            ['entry' => self::SECOND],
            ['url' => '/contact?from=menu#form', 'title' => 'Contact'],
        ]);

        $active = static fn (array $items): array => array_column(array_filter($items, static fn (array $item): bool => $item['active']), 'title');

        $this->assertSame(['Second post'], $active(content()->navigation('main', '/blog/second-post')));
        $this->assertSame(['Second post'], $active(content()->navigation('main', 'blog/second-post/')));
        $this->assertSame(['Contact'], $active(content()->navigation('main', '/contact')));
        $this->assertSame([], $active(content()->navigation('main', '/elsewhere')));
    }

    public function testNavigationTakesTheCurrentUrlFromTheRequest(): void
    {
        $this->writeTree([['entry' => self::FIRST], ['entry' => self::SECOND]]);

        $response = $this->withRoutes([['get', 'blog/first-post', static fn () => service('response')->setJSON(navigation('main'))]])
            ->get('blog/first-post');

        $this->assertSame([true, false], array_column(json_decode($response->getJSON(), true), 'active'));
    }

    public function testItemsForUnpublishedScheduledOrTrashedEntriesAreSkippedWithTheirChildren(): void
    {
        $this->writeTree([
            ['entry' => self::FIRST, 'children' => [
                ['entry' => self::DRAFT, 'children' => [['url' => '/under-the-draft', 'title' => 'Under the draft']]],
                ['entry' => self::FUTURE],
                ['entry' => self::SECOND],
            ]],
            ['entry' => '99999999-9999-4999-8999-999999999999'],
        ]);

        $items = content()->navigation('main', '/');

        $this->assertSame(['First post'], array_column($items, 'title'));
        $this->assertSame(['Second post'], array_column($items[0]['children'], 'title'));

        (new ContentEntryModel())->delete(self::SECOND);

        $this->assertSame([], content()->navigation('main', '/')[0]['children']);
    }

    public function testRenamingALinkedEntrysSlugChangesTheUrlWithoutEditingTheNavFile(): void
    {
        $this->writeTree([['entry' => self::FIRST]]);
        $before = $this->treeFile();

        rename($this->site . 'collections/blog/2026-01-10.first-post.md', $this->site . 'collections/blog/2026-01-10.opening-post.md');
        (new Indexer($this->site))->reindex();

        $this->assertSame('/blog/opening-post', content()->navigation('main', '/')[0]['url']);
        $this->assertSame($before, $this->treeFile());
    }

    public function testAMenuWithoutATreeFileIsEmpty(): void
    {
        $this->assertSame([], content()->navigation('main', '/'));
    }

    public function testATreeFileThatCantBeReadGivesNoItems(): void
    {
        $this->writeTreeFile("- entry: not-a-uuid\n");

        $this->assertSame([], content()->navigation('main', '/'));
    }

    public function testAnEntryWithoutAUriHasNoUrl(): void
    {
        $this->reindexBlogWith('');
        $this->writeTree([['entry' => self::FIRST]]);

        $this->assertSame([['title' => 'First post', 'url' => null, 'active' => false, 'children' => []]], content()->navigation('main', '/'));
    }

    public function testAnUnknownNavigationIsRefused(): void
    {
        $this->expectException(ContentException::class);

        content()->navigation('missing', '/');
    }

    public function testTheConfigReadsTitleMaxDepthAndCollections(): void
    {
        $this->writeNavigation("title: Footer\nmax_depth: 2\ncollections:\n  - blog\n");

        $navigation = Navigation::load($this->site, 'main');

        $this->assertSame('Footer', $navigation->title);
        $this->assertSame(2, $navigation->maxDepth);
        $this->assertSame(['blog'], $navigation->collections);

        $this->writeNavigation('');
        $navigation = Navigation::load($this->site, 'main');

        $this->assertSame('main', $navigation->title);
        $this->assertNull($navigation->maxDepth);
        $this->assertSame([], $navigation->collections);
    }

    #[DataProvider('provideABadNavigationConfigIsRefused')]
    public function testABadNavigationConfigIsRefused(string $config): void
    {
        $this->writeNavigation($config);

        $this->expectException(ContentException::class);
        Navigation::load($this->site, 'main');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideABadNavigationConfigIsRefused(): iterable
    {
        yield 'max_depth is zero' => ["max_depth: 0\n"];

        yield 'max_depth is text' => ["max_depth: deep\n"];

        yield 'collections is not handles' => ["collections:\n  - [blog]\n"];

        yield 'not a mapping' => ["- blog\n"];
    }

    public function testAHandleThatIsNotAFileNameIsRefused(): void
    {
        $this->expectException(ContentException::class);

        Navigation::load($this->site, '../collections/blog');
    }

    // The tree file

    #[DataProvider('provideABadTreeIsRefused')]
    public function testABadTreeIsRefused(string $json): void
    {
        $this->expectException(ContentException::class);

        NavigationTree::fromJson($json);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideABadTreeIsRefused(): iterable
    {
        yield 'not json' => ['{'];

        yield 'not a list' => ['{"entry":"11111111-1111-4111-8111-111111111111"}'];

        yield 'an item that is not a mapping' => ['["home"]'];

        yield 'neither entry nor url' => ['[{"title":"Home"}]'];

        yield 'both entry and url' => ['[{"entry":"11111111-1111-4111-8111-111111111111","url":"/a","title":"A"}]'];

        yield 'an entry that is not a uuid' => ['[{"entry":"home"}]'];

        yield 'a link without a title' => ['[{"url":"/a"}]'];

        yield 'a link with an empty title' => ['[{"url":"/a","title":"  "}]'];

        yield 'a title that is not text' => ['[{"url":"/a","title":["A"]}]'];

        yield 'a javascript url' => ['[{"url":"javascript:alert(1)","title":"A"}]'];

        yield 'a data url' => ['[{"url":"data:text/html,x","title":"A"}]'];

        yield 'a protocol-relative url' => ['[{"url":"//evil.example","title":"A"}]'];

        yield 'a backslash that browsers read as a second slash' => ['[{"url":"/\\\\evil.example","title":"A"}]'];

        yield 'a url with a space' => ['[{"url":"/a b","title":"A"}]'];

        yield 'children that is not a list' => ['[{"url":"/a","title":"A","children":"x"}]'];
    }

    public function testTheTreeKeepsOnlyWhatItNeedsInTheFile(): void
    {
        $tree = NavigationTree::fromJson((string) json_encode([
            ['entry' => strtoupper(self::FIRST), 'title' => '', 'children' => []],
            ['url' => 'mailto:hi@example.com', 'title' => ' Mail ', 'children' => [['url' => '#top', 'title' => 'Top']]],
        ]));

        $this->assertSame([
            ['entry' => self::FIRST],
            ['url' => 'mailto:hi@example.com', 'title' => 'Mail', 'children' => [['url' => '#top', 'title' => 'Top']]],
        ], Yaml::parse($tree->toYaml()));
    }

    // The sample partial

    public function testTheSamplePartialShowsTheMenuWithTheCurrentItemMarked(): void
    {
        $this->writeTree([
            ['entry' => self::FIRST, 'title' => 'Start <here>', 'children' => [['url' => 'https://example.com/?a=1&b=2', 'title' => 'Away']]],
            ['entry' => self::SECOND],
        ]);

        $response = $this->withRoutes([['get', 'blog/second-post', static fn (): string => render('App', '_navigation')]])
            ->get('blog/second-post');

        $xpath = $this->xpath($response->getBody());

        $this->assertSame(['Start <here>', 'Second post'], array_map(static fn ($node): string => $node->textContent, iterator_to_array($xpath->query('//nav/ul/li/a'))));
        $this->assertSame(['Second post'], array_map(static fn ($node): string => $node->textContent, iterator_to_array($xpath->query('//a[@aria-current="page"]'))));
        $this->assertSame('https://example.com/?a=1&b=2', $xpath->query('//nav/ul/li[1]/ul/li/a')->item(0)->getAttribute('href'));
    }

    public function testTheSamplePartialShowsNothingForAnEmptyMenu(): void
    {
        $response = $this->withRoutes([['get', 'menu', static fn (): string => 'before' . render('App', '_navigation') . 'after']])->get('menu');

        $response->assertSee('before');
        $response->assertSee('after');
        $this->assertSame(0, $this->xpath($response->getBody())->query('//nav | //ul')->length);
    }

    // The editor

    public function testBuildingAMenuInTheEditorWritesTheNavTreeFile(): void
    {
        $response = $this->saveTree([
            ['entry' => self::FIRST, 'title' => '', 'children' => [['entry' => self::SECOND, 'title' => 'Next']]],
            ['url' => '/contact', 'title' => 'Contact', 'children' => []],
        ], '');

        $response->assertRedirectTo(site_url(route_to('content-navigation', 'main')));
        $this->assertSame([
            ['entry' => self::FIRST, 'children' => [['entry' => self::SECOND, 'title' => 'Next']]],
            ['url' => '/contact', 'title' => 'Contact'],
        ], Yaml::parse($this->treeFile()));
        $this->assertSame(['Next'], array_column(content()->navigation('main', '/')[0]['children'], 'title'));
    }

    public function testASavedMenuLeavesNoTemporaryFilesBehind(): void
    {
        $this->saveTree([['entry' => self::FIRST]], '');
        $this->saveTree([['entry' => self::SECOND]]);

        $this->assertSame(['main.yaml'], array_values(array_diff(scandir($this->site . 'trees/navigation'), ['.', '..'])));
    }

    public function testTheEditorShowsTheMenuAndOffersTheEntries(): void
    {
        $this->writeTree([
            ['entry' => self::FIRST, 'title' => 'Start', 'children' => [['entry' => self::DRAFT]]],
            ['url' => '/contact', 'title' => 'Contact'],
        ]);

        $response = $this->actingAs($this->admin)->get(route_to('content-navigation', 'main'));

        $response->assertOK();
        $xpath = $this->xpath($response->getBody());

        $this->assertSame(self::FIRST, $xpath->query('//ul[@data-tree]/li[1]')->item(0)->getAttribute('data-entry'));
        $this->assertSame('Start', $xpath->query('//ul[@data-tree]/li[1]/div//input[@data-title-input]')->item(0)->getAttribute('value'));
        $this->assertSame(self::DRAFT, $xpath->query('//ul[@data-tree]/li[1]/ul/li[1]')->item(0)->getAttribute('data-entry'));
        $this->assertSame('/contact', $xpath->query('//ul[@data-tree]/li[2]//input[@data-url-input]')->item(0)->getAttribute('value'));
        $this->assertSame(hash('sha256', $this->treeFile()), $this->formFields($response->getBody(), url_to('content-navigation', 'main'))['hash']);

        // The picker offers every entry, drafts too, because a draft can be linked before it goes live.
        $this->assertCount(4, $xpath->query('//select[@data-entry-select]/option[@value != ""]'));
    }

    public function testThePickerOffersOnlyTheCollectionsTheNavigationAllows(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\n");
        mkdir($this->site . 'collections/pages');
        $this->writeFile('collections/pages/about.md', "---\nid: aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1\ntitle: About\n---\n");
        (new Indexer($this->site))->reindex();

        $xpath = $this->xpath($this->actingAs($this->admin)->get(route_to('content-navigation', 'main'))->getBody());
        $this->assertCount(5, $xpath->query('//select[@data-entry-select]/option[@value != ""]'));

        $this->writeNavigation("collections:\n  - pages\n");

        $xpath = $this->xpath($this->actingAs($this->admin)->get(route_to('content-navigation', 'main'))->getBody());
        $this->assertCount(1, $xpath->query('//select[@data-entry-select]/option[@value != ""]'));
    }

    public function testTheEditorDropsItemsForEntriesThatAreGone(): void
    {
        $this->writeTree([['entry' => self::FIRST, 'children' => [['entry' => self::SECOND]]], ['entry' => self::DRAFT]]);
        (new ContentEntryModel())->delete(self::FIRST);

        $response = $this->actingAs($this->admin)->get(route_to('content-navigation', 'main'));

        $xpath = $this->xpath($response->getBody());
        $this->assertSame(self::SECOND, $xpath->query('//ul[@data-tree]/li[1]')->item(0)->getAttribute('data-entry'));
        $this->assertSame(self::DRAFT, $xpath->query('//ul[@data-tree]/li[2]')->item(0)->getAttribute('data-entry'));
    }

    public function testTheEditorNeedsContentStructure(): void
    {
        $this->writeTree([['entry' => self::FIRST]]);

        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access', 'content.edit', 'content.publish');

        $this->actingAs($user)->get(route_to('content-navigation', 'main'))->assertRedirectTo(site_url(ADMIN_AREA));

        $before = $this->treeFile();
        $this->actingAs($user)->post(route_to('content-navigation', 'main'), ['tree' => '[]', 'hash' => hash('sha256', $before)])
            ->assertRedirectTo(site_url(ADMIN_AREA));
        $this->assertSame($before, $this->treeFile());
    }

    public function testTheSidebarLinksToTheEditorOnlyForThoseWhoCanUseIt(): void
    {
        $dashboard = $this->actingAs($this->admin)->get(ADMIN_AREA);
        $this->assertStringContainsString(route_to('content-navigation', 'main'), $dashboard->getBody());
        $this->assertStringContainsString('Main menu', $dashboard->getBody());

        Services::resetSingle('menus');
        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access');

        $dashboard = $this->actingAs($user)->get(ADMIN_AREA);
        $this->assertStringNotContainsString(route_to('content-navigation', 'main'), $dashboard->getBody());
    }

    public function testAnUnknownNavigationHasNoEditor(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('content-navigation', 'missing'));
    }

    public function testAMenuDeeperThanMaxDepthIsRefusedAndNothingChanges(): void
    {
        $this->writeNavigation("max_depth: 2\n");
        $this->writeTree([['entry' => self::FIRST]]);
        $before = $this->treeFile();

        $tooDeep = [['entry' => self::FIRST, 'children' => [['entry' => self::SECOND, 'children' => [['url' => '/a', 'title' => 'A']]]]]];

        $this->assertFalse($this->saveTree($tooDeep)->isRedirect());
        $this->assertSame($before, $this->treeFile());

        $this->saveTree([['entry' => self::FIRST, 'children' => [['entry' => self::SECOND]]]])->assertRedirect();
    }

    public function testAnEntryOutsideTheAllowedCollectionsOrThatDoesNotExistIsRefused(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\n");
        $this->writeNavigation("collections:\n  - pages\n");
        $this->writeTree([['url' => '/a', 'title' => 'A']]);
        $before = $this->treeFile();

        $this->saveTree([['entry' => self::FIRST]])->assertStatus(422);
        $this->saveTree([['entry' => '99999999-9999-4999-8999-999999999999']])->assertStatus(422);
        $this->assertSame($before, $this->treeFile());
    }

    public function testATrashedEntryCantBeAddedToAMenu(): void
    {
        (new ContentEntryModel())->delete(self::FIRST);

        $this->saveTree([['entry' => self::FIRST]], '')->assertStatus(422);
        $this->assertFileDoesNotExist($this->site . self::TREE_FILE);
    }

    public function testAMenuThatChangedOnDiskSinceItWasOpenedIsRefused(): void
    {
        $this->writeTree([['entry' => self::FIRST]]);
        $opened = $this->treeFile();
        $edited = $opened . "# edited by hand\n";
        file_put_contents($this->site . self::TREE_FILE, $edited);

        $response = $this->saveTree([['entry' => self::SECOND]], $opened);

        $response->assertStatus(409);
        $this->assertSame($edited, $this->treeFile());
    }

    public function testAMenuThatDidntExistWhenOpenedCanBeCreatedButNotOverwrittenBehindTheEditorsBack(): void
    {
        $this->saveTree([['entry' => self::FIRST]], '')->assertRedirect();
        $this->assertFileExists($this->site . self::TREE_FILE);

        $this->saveTree([['entry' => self::SECOND]], '')->assertStatus(409);
        $this->assertSame([['entry' => self::FIRST]], Yaml::parse($this->treeFile()));
    }

    public function testAnUnreadableTreeFileOpensAsAnEmptyMenuWithTheReasonAndSavingReplacesIt(): void
    {
        $this->writeTreeFile("- entry: not-a-uuid\n");

        $response = $this->actingAs($this->admin)->get(route_to('content-navigation', 'main'));

        $response->assertOK();
        $this->assertCount(1, $this->xpath($response->getBody())->query('//*[@data-notice]'));
        $this->assertCount(0, $this->xpath($response->getBody())->query('//ul[@data-tree]/li'));

        $this->saveTree([['entry' => self::FIRST]], "- entry: not-a-uuid\n")->assertRedirect();
        $this->assertSame([['entry' => self::FIRST]], Yaml::parse($this->treeFile()));
    }

    #[DataProvider('provideAMalformedPostedMenuIsRefused')]
    public function testAMalformedPostedMenuIsRefused(string $posted): void
    {
        $this->writeTree([['entry' => self::FIRST]]);
        $before = $this->treeFile();

        $response = $this->actingAs($this->admin)->post(route_to('content-navigation', 'main'), ['tree' => $posted, 'hash' => hash('sha256', $before)]);

        $response->assertStatus(422);
        $this->assertSame($before, $this->treeFile());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAMalformedPostedMenuIsRefused(): iterable
    {
        yield 'not json' => ['{'];

        yield 'a javascript link' => ['[{"url":"javascript:alert(1)","title":"A"}]'];

        yield 'a link with no title' => ['[{"url":"/a","title":""}]'];

        yield 'an item with neither' => ['[{"title":"A"}]'];
    }

    // Helpers

    private function writeNavigation(string $config): void
    {
        @mkdir($this->site . 'navigation');
        $this->writeFile('navigation/main.yaml', $config);
    }

    /**
     * @param list<array<string, mixed>> $tree
     */
    private function writeTree(array $tree): void
    {
        $this->writeTreeFile(Yaml::dump($tree, 10, 2));
    }

    private function writeTreeFile(string $contents): void
    {
        @mkdir($this->site . 'trees/navigation', 0775, true);
        $this->writeFile(self::TREE_FILE, $contents);
    }

    private function treeFile(): string
    {
        return (string) file_get_contents($this->site . self::TREE_FILE);
    }

    /**
     * Posts a menu the way the editor does.
     *
     * @param list<array<string, mixed>> $tree
     * @param string|null                $openedOn the tree file the editor was opened on, as it was then
     */
    private function saveTree(array $tree, ?string $openedOn = null): TestResponse
    {
        return $this->actingAs($this->admin)->post(route_to('content-navigation', 'main'), [
            'tree' => json_encode($tree),
            'hash' => NavigationTree::hash($openedOn ?? $this->treeFile()),
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
