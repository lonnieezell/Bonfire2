<?php

namespace Tests\Content;

use Bonfire\Content\ContentException;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Content\NavigationItem;
use Bonfire\Content\NavigationTree;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\TestLogger;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\SubmitsForms;

/**
 * Navigation menus: navigation(), the admin editor and the App theme's sample partial.
 *
 * @internal
 */
final class NavigationTest extends ContentTestCase
{
    use SubmitsForms;

    private const HOME  = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1';
    private const ABOUT = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa2';
    private const TEAM  = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa3';
    private const DRAFT = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa4';
    private const SOON  = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa5';
    private const NOTE  = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa6';

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        $this->writePages();
        (new Indexer($this->site))->reindex();

        mkdir($this->site . 'navigation');
        mkdir($this->site . 'trees/navigation', 0777, true);
        $this->writeFile('navigation/main.yaml', "title: Main menu\nmax_depth: 2\ncollections:\n  - pages\n");
    }

    protected function tearDown(): void
    {
        foreach (['auth', 'bonfire', 'menus', 'widgets', 'request'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    // navigation()

    public function testItReturnsTheTreeWithTitlesAndUrlsFromTheEntriesAndTheLinks(): void
    {
        $this->writeTree([
            ['entry' => self::HOME, 'title' => 'Start'],
            ['entry' => self::ABOUT, 'children' => [
                ['entry' => self::TEAM],
                ['url' => '/contact', 'title' => 'Contact'],
            ]],
            ['url' => 'https://example.org/docs', 'title' => 'Docs'],
        ]);

        $this->assertSame([
            ['Start', site_url('/home'), []],
            ['About', site_url('/about'), [
                ['Team', site_url('/about/team'), []],
                ['Contact', site_url('/contact'), []],
            ]],
            ['Docs', 'https://example.org/docs', []],
        ], $this->shape(navigation('main')));
    }

    public function testAnUnknownMenuIsRefused(): void
    {
        $this->expectException(ContentException::class);

        navigation('nope');
    }

    public function testAMenuWithNoTreeFileIsEmpty(): void
    {
        $this->assertSame([], navigation('main'));
    }

    public function testTheItemAtTheCurrentUrlIsActiveAndNoOther(): void
    {
        $this->writeTree([
            ['entry' => self::HOME],
            ['entry' => self::ABOUT, 'children' => [['entry' => self::TEAM]]],
            ['url' => '/contact/', 'title' => 'Contact'],
            ['url' => 'https://example.org/about/team', 'title' => 'Elsewhere'],
        ]);

        $this->visit('about/team');
        [$home, $about, $contact, $elsewhere] = navigation('main');

        $this->assertFalse($home->active);
        $this->assertFalse($about->active);
        $this->assertTrue($about->children[0]->active);
        $this->assertFalse($contact->active);
        $this->assertFalse($elsewhere->active);

        $this->visit('contact');
        $this->assertTrue(navigation('main')[2]->active);
    }

    public function testALinkIsActiveOnItsPathWhateverItsQueryFragmentOrEncoding(): void
    {
        $this->writeTree([
            ['url' => '/contact#team', 'title' => 'Fragment'],
            ['url' => '/contact?a=b', 'title' => 'Query'],
            ['url' => '/caf%C3%A9', 'title' => 'Encoded'],
        ]);

        $this->visit('contact');
        [$fragment, $query, $encoded] = navigation('main');

        $this->assertTrue($fragment->active);
        $this->assertTrue($query->active);
        $this->assertFalse($encoded->active);

        $this->visit('caf%C3%A9');
        $this->assertTrue(navigation('main')[2]->active);
    }

    public function testAMalformedLocalUrlIsNotActiveOnTheHomePage(): void
    {
        $this->writeTree([['url' => '/:80', 'title' => 'Odd']]);

        $this->visit('/');

        $this->assertFalse(navigation('main')[0]->active);
    }

    public function testATitleYamlReadsAsANumberIsStillATitle(): void
    {
        $this->writeFile('trees/navigation/main.yaml', "- url: /archive\n  title: 2024\n");

        $this->assertSame([['2024', site_url('/archive'), []]], $this->shape(navigation('main')));
    }

    public function testAnItemWhoseEntryCantBeShownIsSkippedWithItsChildren(): void
    {
        $this->writeTree([
            ['entry' => self::DRAFT, 'children' => [['entry' => self::HOME]]],
            ['entry' => self::SOON, 'children' => [['url' => '/a', 'title' => 'A']]],
            ['entry' => self::NOTE],
            ['entry' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'],
            ['entry' => self::ABOUT, 'children' => [['entry' => self::DRAFT]]],
        ]);

        $this->assertSame([['About', site_url('/about'), []]], $this->shape(navigation('main')));
    }

    public function testATrashedEntryIsSkippedWithItsChildren(): void
    {
        $this->writeTree([
            ['entry' => self::ABOUT, 'children' => [['entry' => self::TEAM]]],
            ['entry' => self::HOME],
        ]);

        (new ContentEntryModel())->delete(self::ABOUT);

        $this->assertSame([['Home', site_url('/home'), []]], $this->shape(navigation('main')));
    }

    public function testRenamingALinkedEntrysSlugChangesTheUrlWithoutEditingTheTree(): void
    {
        $this->writeTree([['entry' => self::ABOUT, 'children' => [['entry' => self::TEAM]]]]);
        $tree = file_get_contents($this->site . 'trees/navigation/main.yaml');

        rename($this->site . 'collections/pages/about.md', $this->site . 'collections/pages/company.md');
        (new Indexer($this->site))->reindex();

        $this->assertSame([
            ['About', site_url('/company'), [['Team', site_url('/company/team'), []]]],
        ], $this->shape(navigation('main')));
        $this->assertSame($tree, file_get_contents($this->site . 'trees/navigation/main.yaml'));
    }

    #[DataProvider('provideABrokenTreeFileIsAnEmptyMenuSoItCantTakeThePageDown')]
    public function testABrokenTreeFileIsAnEmptyMenuSoItCantTakeThePageDown(string $raw): void
    {
        $this->writeFile('trees/navigation/main.yaml', $raw);

        $this->assertSame([], navigation('main'));
        $this->assertTrue(TestLogger::didLog('error', 'Content: navigation main.yaml', false));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideABrokenTreeFileIsAnEmptyMenuSoItCantTakeThePageDown(): iterable
    {
        return [
            'not YAML'         => ["- [\n"],
            'not a list'       => ["entry: nope\n"],
            'neither'          => ["- title: Nothing\n"],
            'both'             => ['- entry: ' . self::HOME . "\n  url: /a\n  title: A\n"],
            'entry not a UUID' => ["- entry: nope\n"],
            'link no title'    => ["- url: /a\n"],
            'script URL'       => ["- url: 'javascript:alert(1)'\n  title: A\n"],
            'bad children'     => ["- url: /a\n  title: A\n  children: nope\n"],
        ];
    }

    // The admin editor

    public function testTheEditorNeedsContentStructure(): void
    {
        $this->writeTree([['entry' => self::HOME]]);
        $before = $this->treeFile();

        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access', 'content.edit', 'content.publish');

        $this->actingAs($user)->get(route_to('content-navigation', 'main'))->assertRedirectTo(site_url(ADMIN_AREA));
        $this->actingAs($user)->post(route_to('content-navigation', 'main'), ['tree' => '[]', 'hash' => hash('sha256', $before)])
            ->assertRedirectTo(site_url(ADMIN_AREA));

        $this->assertSame($before, $this->treeFile());
    }

    public function testAnUnknownMenuHasNoEditor(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin())->get(route_to('content-navigation', 'nope'));
    }

    public function testTheEditorShowsTheItemsNestedWithTheHashOfTheFile(): void
    {
        $this->writeTree([
            ['entry' => self::HOME, 'title' => 'Start'],
            ['entry' => self::DRAFT, 'children' => [['url' => '/contact', 'title' => 'Contact']]],
        ]);

        $response = $this->actingAs($this->admin())->get(route_to('content-navigation', 'main'));

        $response->assertOK();
        $xpath = $this->xpath($response->getBody());

        $this->assertSame(self::HOME, $xpath->query('//ul[@data-tree]/li[1]')->item(0)->getAttribute('data-entry'));
        $this->assertSame('Start', $xpath->query('//ul[@data-tree]/li[1]//input[@data-title]')->item(0)->getAttribute('value'));
        $this->assertSame(self::DRAFT, $xpath->query('//ul[@data-tree]/li[2]')->item(0)->getAttribute('data-entry'));
        $this->assertSame(1, $xpath->query('//ul[@data-tree]/li[2]//*[@data-hidden]')->length);
        $this->assertSame(0, $xpath->query('//ul[@data-tree]/li[1]//*[@data-hidden]')->length);
        $this->assertSame('/contact', $xpath->query('//ul[@data-tree]/li[2]/ul/li[1]//input[@data-url]')->item(0)->getAttribute('value'));
        $this->assertSame(hash('sha256', $this->treeFile()), $this->formFields($response->getBody(), url_to('content-navigation', 'main'))['hash']);
    }

    public function testTheEditorMarksEveryItemThatWontShow(): void
    {
        $this->writeFile('navigation/main.yaml', "title: Main menu\ncollections:\n  - pages\n  - notes\n");
        $this->writeTree([
            ['entry' => self::SOON],
            ['entry' => self::NOTE],
            ['entry' => self::TEAM],
            ['entry' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'],
        ]);
        (new ContentEntryModel())->delete(self::TEAM);

        $xpath = $this->xpath($this->actingAs($this->admin())->get(route_to('content-navigation', 'main'))->getBody());

        $this->assertSame(4, $xpath->query('//ul[@data-tree]/li//*[@data-hidden]')->length);
    }

    public function testThePickerShowsEntriesOfCollectionsThatShareATitle(): void
    {
        $this->writeFile('navigation/main.yaml', "title: Main menu\ncollections:\n  - pages\n  - more\n");
        $this->writeFile('collections/more.yaml', "title: Pages\nroute: /more/{slug}\n");
        mkdir($this->site . 'collections/more');
        $this->writeFile('collections/more/extra.md', "---\nid: bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb1\ntitle: Extra\n---\nBody.\n");
        (new Indexer($this->site))->reindex();

        $xpath = $this->xpath($this->actingAs($this->admin())->get(route_to('content-navigation', 'main'))->getBody());

        $this->assertSame(6, $xpath->query('//select[@data-entry-picker]/optgroup/option')->length);
    }

    public function testTheUrlFieldsAcceptWhatTheServerDoes(): void
    {
        $this->writeTree([['url' => '/a', 'title' => 'A']]);

        $xpath   = $this->xpath($this->actingAs($this->admin())->get(route_to('content-navigation', 'main'))->getBody());
        $pattern = $xpath->query('//ul[@data-tree]//input[@data-url]')->item(0)->getAttribute('pattern');

        $this->assertSame($pattern, $xpath->query('//form[@data-add-link]//input[@name="url"]')->item(0)->getAttribute('pattern'));

        foreach (['/a', '#a', '?a', 'http://a', 'https://a', 'mailto:a@b.c', 'tel:1'] as $url) {
            $this->assertMatchesRegularExpression("~^(?:{$pattern})$~", $url);
            NavigationTree::fromJson(json_encode([['url' => $url, 'title' => 'A']]));
        }

        foreach (['example.com', 'javascript:alert(1)', ' /a'] as $url) {
            $this->assertDoesNotMatchRegularExpression("~^(?:{$pattern})$~", $url);
        }
    }

    public function testThePickerOffersOnlyEntriesItCanLinkTo(): void
    {
        $this->writeFile('navigation/main.yaml', "title: Main menu\ncollections:\n  - pages\n  - notes\n");
        $this->writeFile('collections/blog.yaml', "title: Blog\ndated: true\nroute: /blog/{slug}\n");
        (new Indexer($this->site))->reindex();

        $response = $this->actingAs($this->admin())->get(route_to('content-navigation', 'main'));
        $options  = $this->xpath($response->getBody())->query('//select[@data-entry-picker]/optgroup/option');
        $offered  = [];

        foreach ($options as $option) {
            $offered[] = $option->getAttribute('value');
        }

        sort($offered);
        // Not the blog's, which the menu doesn't list, or the note, which has no URI.
        $this->assertSame([self::HOME, self::ABOUT, self::TEAM, self::DRAFT, self::SOON], $offered);
    }

    public function testBuildingAMenuWritesTheTreeFile(): void
    {
        $response = $this->saveTree([
            ['entry' => self::HOME, 'title' => '', 'children' => []],
            ['entry' => self::ABOUT, 'title' => 'About us', 'children' => [
                ['url' => '/contact', 'title' => 'Contact', 'children' => []],
            ]],
        ]);

        $response->assertRedirectTo(site_url(route_to('content-navigation', 'main')));
        $this->assertSame([
            ['entry' => self::HOME],
            ['entry' => self::ABOUT, 'title' => 'About us', 'children' => [
                ['url' => '/contact', 'title' => 'Contact'],
            ]],
        ], Yaml::parse($this->treeFile()));
        $this->assertSame('About us', navigation('main')[1]->title);
    }

    public function testATreeFileIsCreatedWhereThereWasNone(): void
    {
        rmdir($this->site . 'trees/navigation');

        $this->saveTree([['url' => '/a', 'title' => 'A']], '');

        $this->assertSame([['url' => '/a', 'title' => 'A']], Yaml::parse($this->treeFile()));
    }

    public function testATreeThatChangedOnDiskSinceItWasOpenedIsRefused(): void
    {
        $this->writeTree([['entry' => self::HOME]]);
        $openedOn = $this->treeFile();
        $this->writeTree([['entry' => self::ABOUT]]);
        $changed = $this->treeFile();

        $response = $this->saveTree([['entry' => self::TEAM]], $openedOn);

        $response->assertStatus(409);
        $this->assertSame($changed, $this->treeFile());
        $this->assertSame(1, $this->xpath($response->getBody())->query('//a[@data-reload]')->length);
    }

    public function testATreeDeeperThanMaxDepthIsRefused(): void
    {
        $this->saveTree([['url' => '/a', 'title' => 'A', 'children' => [['url' => '/b', 'title' => 'B', 'children' => [['url' => '/c', 'title' => 'C']]]]]])
            ->assertStatus(422);

        $this->assertFileDoesNotExist($this->site . 'trees/navigation/main.yaml');
    }

    public function testAnEntryOfACollectionTheMenuDoesntListIsRefused(): void
    {
        $this->saveTree([['entry' => self::NOTE]])->assertStatus(422);

        $this->assertFileDoesNotExist($this->site . 'trees/navigation/main.yaml');
    }

    public function testAnEntryThatDoesntExistIsRefused(): void
    {
        $this->saveTree([['entry' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb']])->assertStatus(422);

        $this->assertFileDoesNotExist($this->site . 'trees/navigation/main.yaml');
    }

    public function testAnItemLinkingToATrashedEntryCanBeSavedSoItComesBackWithTheEntry(): void
    {
        (new ContentEntryModel())->delete(self::ABOUT);

        $this->saveTree([['entry' => self::ABOUT]])->assertRedirect();

        $this->assertSame([['entry' => self::ABOUT]], Yaml::parse($this->treeFile()));
    }

    #[DataProvider('provideAMalformedPostedTreeIsRefusedAndTheFormStaysUsable')]
    public function testAMalformedPostedTreeIsRefusedAndTheFormStaysUsable(string $posted): void
    {
        $response = $this->actingAs($this->admin())->post(route_to('content-navigation', 'main'), ['tree' => $posted, 'hash' => hash('sha256', '')]);

        $response->assertStatus(422);
        $this->assertSame(1, $this->xpath($response->getBody())->query('//*[@data-notice]//a[@data-reload]')->length);
        $this->assertFileDoesNotExist($this->site . 'trees/navigation/main.yaml');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideAMalformedPostedTreeIsRefusedAndTheFormStaysUsable(): iterable
    {
        return [
            'not JSON'   => ['nope'],
            'script URL' => ['[{"url":"javascript:alert(1)","title":"A"}]'],
            'hidden tab' => ['[{"url":"java\tscript:alert(1)","title":"A"}]'],
            'no title'   => ['[{"url":"/a","title":" "}]'],
            'no url'     => ['[{"title":"A"}]'],
            'both'       => ['[{"url":"/a","title":"A","entry":"' . self::HOME . '"}]'],
            'not a list' => ['{"url":"/a","title":"A"}'],
            'bad title'  => ['[{"url":"/a","title":["A"]}]'],
            'bad entry'  => ['[{"entry":["a"]}]'],
        ];
    }

    public function testATreeFileThatCantBeReadOpensEmptyWithTheReason(): void
    {
        $this->writeFile('trees/navigation/main.yaml', "nope: nope\n");

        $response = $this->actingAs($this->admin())->get(route_to('content-navigation', 'main'));

        $response->assertOK();
        $xpath = $this->xpath($response->getBody());
        $this->assertSame(1, $xpath->query('//*[@data-notice]')->length);
        $this->assertSame(0, $xpath->query('//ul[@data-tree]/li')->length);
    }

    public function testEachMenuHasASidebarItemForThoseWithContentStructure(): void
    {
        $this->actingAs($this->admin())->get(ADMIN_AREA)->assertSee(route_to('content-navigation', 'main'));

        Services::resetSingle('auth');
        Services::resetSingle('menus');

        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access');

        $this->assertStringNotContainsString(route_to('content-navigation', 'main'), $this->actingAs($user)->get(ADMIN_AREA)->getBody());
    }

    // The App theme's partial

    public function testThePartialRendersTheNestedMenuMarkingTheActiveItemAndEscaping(): void
    {
        $this->writeTree([
            ['entry' => self::ABOUT, 'title' => 'Us <b>', 'children' => [['entry' => self::TEAM]]],
            ['url' => '/a"b', 'title' => 'A'],
        ]);
        $this->visit('about/team');

        $xpath = $this->xpath(render('App', '_navigation'));

        $this->assertSame('Us <b>', trim($xpath->query('/html/body/ul/li[1]/a')->item(0)->textContent));
        $this->assertSame(site_url('/a"b'), $xpath->query('/html/body/ul/li[2]/a')->item(0)->getAttribute('href'));
        $this->assertSame(site_url('/about/team'), $xpath->query('/html/body/ul/li[1]/ul/li[1]/a')->item(0)->getAttribute('href'));

        $current = $xpath->query('//a[@aria-current="page"]');
        $this->assertSame(1, $current->length);
        $this->assertSame('Team', trim($current->item(0)->textContent));
    }

    private function writePages(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\nstructure: true\nroute: /{slug}\nblueprints:\n  - page\n");
        mkdir($this->site . 'collections/pages');
        mkdir($this->site . 'trees/collections', 0777, true);
        $this->writeFile('trees/collections/pages.yaml', Yaml::dump([
            ['id' => self::HOME],
            ['id' => self::ABOUT, 'children' => [['id' => self::TEAM]]],
            ['id' => self::DRAFT],
            ['id' => self::SOON],
        ], 10, 2));

        foreach ([self::HOME => 'home', self::ABOUT => 'about', self::TEAM => 'team'] as $id => $slug) {
            $this->writeFile("collections/pages/{$slug}.md", "---\nid: {$id}\ntitle: " . ucfirst($slug) . "\n---\nBody.\n");
        }

        $this->writeFile('collections/pages/draft.md', "---\nid: " . self::DRAFT . "\ntitle: Draft\nstatus: draft\n---\nBody.\n");
        $this->writeFile('collections/pages/soon.md', "---\nid: " . self::SOON . "\ntitle: Soon\npublished_at: 2099-01-01 00:00:00\n---\nBody.\n");

        // A collection without a route, whose entries have no URI to link to.
        $this->writeFile('collections/notes.yaml', "title: Notes\n");
        mkdir($this->site . 'collections/notes');
        $this->writeFile('collections/notes/note.md', "---\nid: " . self::NOTE . "\ntitle: Note\n---\nBody.\n");
    }

    /**
     * @param list<array<string, mixed>> $tree
     */
    private function writeTree(array $tree): void
    {
        $this->writeFile('trees/navigation/main.yaml', Yaml::dump($tree, 10, 2));
    }

    private function admin(): User
    {
        $admin = $this->createUser();
        $admin->addGroup('superadmin');

        return $admin;
    }

    private function treeFile(): string
    {
        return (string) @file_get_contents($this->site . 'trees/navigation/main.yaml');
    }

    /**
     * Posts a tree the way the editor does.
     *
     * @param list<array<string, mixed>> $tree
     */
    private function saveTree(array $tree, ?string $openedOn = null): TestResponse
    {
        return $this->actingAs($this->admin())->post(route_to('content-navigation', 'main'), [
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

    private function visit(string $path): void
    {
        Services::injectMock('request', new IncomingRequest(config('App'), new SiteURI(config('App'), $path), null, new UserAgent()));
    }

    /**
     * @param list<NavigationItem> $items
     *
     * @return list<array{string, string, list<mixed>}> the title, the URL and the children of each
     */
    private function shape(array $items): array
    {
        return array_map(fn (NavigationItem $item): array => [$item->title, $item->url, $this->shape($item->children)], $items);
    }
}
