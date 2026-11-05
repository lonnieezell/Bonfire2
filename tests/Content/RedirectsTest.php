<?php

namespace Tests\Content;

use Bonfire\Content\Collection;
use Bonfire\Content\EntryStore;
use Bonfire\Content\Indexer;
use Bonfire\Content\ReindexResult;
use Bonfire\Content\Structure;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Symfony\Component\Yaml\Yaml;
use Tests\Support\Content\BlogController;
use Tests\Support\Content\ContentTestCase;

/**
 * The old URI of an entry that was renamed or moved keeps working, from `redirects.yaml`.
 *
 * @internal
 */
final class RedirectsTest extends ContentTestCase
{
    private const HOME    = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1';
    private const ABOUT   = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa2';
    private const TEAM    = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa3';
    private const HISTORY = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa4';
    private const JOBS    = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa5';
    private const TREE    = 'trees/collections/pages.yaml';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        $this->writePages();
        mkdir($this->site . 'trees/collections', 0775, true);
        file_put_contents($this->site . self::TREE, Yaml::dump([
            ['id' => self::HOME],
            ['id' => self::ABOUT, 'children' => [
                ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]],
                ['id' => self::HISTORY],
            ]],
        ], 10, 2));
        $this->reindex();

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        $this->resetRouting();

        parent::tearDown();
    }

    // Recording

    public function testNothingIsWrittenWhileNoUriHasChanged(): void
    {
        $this->reindex();

        $this->assertFileDoesNotExist($this->site . 'redirects.yaml');
    }

    public function testMovingAPageInTheAdminRecordsItsOldUriAndThoseOfThePagesBelowIt(): void
    {
        $this->saveTree([
            ['id' => self::HOME],
            ['id' => self::ABOUT, 'children' => [['id' => self::HISTORY]]],
            ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]],
        ]);

        $this->assertSame('/team', $this->uriOf(self::TEAM));
        $this->assertSame([
            '/about/team'      => self::TEAM,
            '/about/team/jobs' => self::JOBS,
        ], $this->redirects());
    }

    public function testRenamingAFileRecordsTheOldUriWhenTheReindexFindsIt(): void
    {
        rename($this->site . 'collections/pages/about.md', $this->site . 'collections/pages/company.md');

        $result = $this->reindex();

        $this->assertSame([], $result->errors);
        $this->assertSame([
            '/about'           => self::ABOUT,
            '/about/history'   => self::HISTORY,
            '/about/team'      => self::TEAM,
            '/about/team/jobs' => self::JOBS,
        ], $this->redirects());
    }

    public function testTheFileIsKeptInOrderAndReadableByHand(): void
    {
        rename($this->site . 'collections/pages/team.md', $this->site . 'collections/pages/crew.md');
        $this->reindex();

        $this->assertSame('/about/team: ' . self::TEAM . "\n/about/team/jobs: " . self::JOBS . "\n", (string) file_get_contents($this->site . 'redirects.yaml'));
    }

    // Serving

    public function testAnOldUriGetsA301ToTheCurrentOne(): void
    {
        rename($this->site . 'collections/pages/about.md', $this->site . 'collections/pages/company.md');
        $this->reindex();

        $result = $this->visit('about/team');

        $result->assertStatus(301);
        $result->assertRedirectTo(site_url('company/team'));
    }

    public function testTheRedirectAnswersWithoutAutoRoutingToHandWrittenRoutes(): void
    {
        $this->saveTree([['id' => self::TEAM], ['id' => self::HOME], ['id' => self::ABOUT, 'children' => [['id' => self::HISTORY]]], ['id' => self::JOBS]]);

        $this->assertSame('/team', content()->redirectFor('/about/team'));
        $this->assertSame('/team', content()->redirectFor('about/team/'));
        $this->assertSame('/jobs', content()->redirectFor('/about/team/jobs'));
        $this->assertNull(content()->redirectFor('/nowhere'));
        $this->assertNull(content()->redirectFor('/team'));
    }

    public function testFindByUriStillTreatsAnOldUriAsMissing(): void
    {
        $this->moveAboutToCompany();

        $this->assertNull(content()->findByUri('/about'));
        $this->assertSame(self::ABOUT, content()->findByUri('/company')?->id);
    }

    // Chains and loops

    public function testAChainCollapsesSoEveryOldUriPointsAtTheLatestOne(): void
    {
        $this->moveAboutToCompany();
        rename($this->site . 'collections/pages/company.md', $this->site . 'collections/pages/firm.md');
        $this->reindex();

        $this->assertSame('/firm', content()->redirectFor('/about'));
        $this->assertSame('/firm', content()->redirectFor('/company'));
        $this->assertSame('/firm/team', content()->redirectFor('/about/team'));
        $this->assertSame('/firm/team', content()->redirectFor('/company/team'));
        $this->visit('about')->assertRedirectTo(site_url('firm'));
    }

    public function testMovingBackToAnOldUriLeavesNoLoop(): void
    {
        $this->moveAboutToCompany();
        rename($this->site . 'collections/pages/company.md', $this->site . 'collections/pages/about.md');
        $this->reindex();

        $this->assertSame('/about', content()->redirectFor('/company'));
        $this->assertNull(content()->redirectFor('/about'));
        $this->assertArrayNotHasKey('/about', $this->redirects());
        $this->visit('about')->assertStatus(201);
        $this->visit('company')->assertRedirectTo(site_url('about'));
    }

    public function testMovingBackInTheAdminLeavesNoLoopEither(): void
    {
        $tree = [['id' => self::HOME], ['id' => self::ABOUT, 'children' => [['id' => self::HISTORY]]], ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]]];
        $this->saveTree($tree);
        $this->saveTree([
            ['id' => self::HOME],
            ['id' => self::ABOUT, 'children' => [['id' => self::TEAM, 'children' => [['id' => self::JOBS]]], ['id' => self::HISTORY]]],
        ]);

        $this->assertSame([
            '/team'      => self::TEAM,
            '/team/jobs' => self::JOBS,
        ], $this->redirects());
        $this->assertNull(content()->redirectFor('/about/team'));
    }

    // Who wins

    public function testALiveEntryAtTheUriWinsOverTheRedirectFromIt(): void
    {
        $this->moveAboutToCompany();
        $this->assertSame('/company', content()->redirectFor('/about'));

        $this->writeFile('collections/pages/about.md', "---\nid: bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb\ntitle: A new about\n---\n");
        $this->reindex();

        $this->assertNull(content()->redirectFor('/about'));
        $this->assertArrayNotHasKey('/about', $this->redirects());
        $this->visit('about')->assertStatus(201);
    }

    public function testANewEntryInTheAdminTakingAnOldUriDropsTheRedirectFromIt(): void
    {
        $this->moveAboutToCompany();

        (new EntryStore($this->site))->create(Collection::load($this->site, 'pages'), 'about', ['title' => 'A new about'], 'Body.', null);

        $this->assertArrayNotHasKey('/about', $this->redirects());
        $this->assertNull(content()->redirectFor('/about'));
        $this->assertSame('/company/team', content()->redirectFor('/about/team'));
    }

    public function testAnEntryStillWinsWhenTheRedirectIsAddedByHand(): void
    {
        file_put_contents($this->site . 'redirects.yaml', '/about: ' . self::TEAM . "\n");

        $this->assertNull(content()->redirectFor('/about'));
        $this->visit('about')->assertStatus(201);
    }

    // Targets that would be a 404

    public function testATrashedTargetGetsNoRedirectUntilItIsRestored(): void
    {
        $this->moveAboutToCompany();

        $this->actingAs($this->admin)->post(ADMIN_AREA . '/content/pages/' . self::TEAM . '/delete', ['descendants' => 1]);
        $this->resetRouting();

        $this->assertNull(content()->redirectFor('/about/team'));
        $this->assertNotFound('about/team');
        $this->assertSame('/company', content()->redirectFor('/about'));

        $this->assertTrue((new EntryStore($this->site))->restore(self::TEAM));
        $this->assertSame('/company/team', content()->redirectFor('/about/team'));
    }

    public function testAnUnpublishedTargetGetsNoRedirect(): void
    {
        $this->moveAboutToCompany();

        $file = $this->site . 'collections/pages/team.md';
        file_put_contents($file, str_replace("title: Team\n", "title: Team\nstatus: draft\n", (string) file_get_contents($file)));
        $this->reindex();

        $this->assertNull(content()->redirectFor('/about/team'));
        $this->assertNotFound('about/team');
        $this->assertSame('/company', content()->redirectFor('/about'));
    }

    public function testAnEntryThatIsGoneGetsNoRedirect(): void
    {
        $this->moveAboutToCompany();
        unlink($this->site . 'collections/pages/history.md');
        $this->reindex();

        $this->assertNull(content()->redirectFor('/about/history'));
        $this->assertNotFound('about/history');
    }

    // The reindex

    public function testAFreshReindexKeepsEveryRedirect(): void
    {
        $this->moveAboutToCompany();
        $before = (string) file_get_contents($this->site . 'redirects.yaml');

        $result = (new Indexer($this->site))->reindex(true);

        $this->assertSame([], $result->errors);
        $this->assertSame($before, (string) file_get_contents($this->site . 'redirects.yaml'));
        $this->assertSame('/company/team', content()->redirectFor('/about/team'));
    }

    public function testAFreshReindexStillFindsAUriThatChangedInTheFiles(): void
    {
        rename($this->site . 'collections/pages/about.md', $this->site . 'collections/pages/company.md');

        (new Indexer($this->site))->reindex(true);

        $this->assertSame('/company', content()->redirectFor('/about'));
        $this->assertSame('/company/team', content()->redirectFor('/about/team'));
    }

    public function testARedirectsFileThatCantBeReadIsReportedAndNotOverwritten(): void
    {
        file_put_contents($this->site . 'redirects.yaml', "/about: [\n");
        rename($this->site . 'collections/pages/about.md', $this->site . 'collections/pages/company.md');

        $result = $this->reindex();

        $this->assertArrayHasKey('redirects.yaml', $result->errors);
        $this->assertSame("/about: [\n", (string) file_get_contents($this->site . 'redirects.yaml'));
        $this->assertSame('/company', content()->findByUri('/company')?->uri);
        $this->assertNull(content()->redirectFor('/about'));
        $this->assertLogContains('error', 'Content: redirects.yaml');
        $this->assertNotFound('about');
    }

    public function testAMoveInTheAdminIsSavedEvenWhenTheRedirectsFileCantBeRead(): void
    {
        file_put_contents($this->site . 'redirects.yaml', "/about: [\n");

        $response = $this->saveTree([['id' => self::TEAM], ['id' => self::HOME], ['id' => self::ABOUT, 'children' => [['id' => self::HISTORY]]], ['id' => self::JOBS]]);

        $response->assertStatus(422);
        $response->assertSee('old URIs could not be kept');
        $this->assertSame("/about: [\n", (string) file_get_contents($this->site . 'redirects.yaml'));
        $this->assertSame('/team', $this->uriOf(self::TEAM));
    }

    public function testOnlyGetRequestsAreRedirected(): void
    {
        $this->moveAboutToCompany();

        $this->assertNotFoundOnPost('about');
    }

    private function moveAboutToCompany(): ReindexResult
    {
        rename($this->site . 'collections/pages/about.md', $this->site . 'collections/pages/company.md');

        return $this->reindex();
    }

    private function writePages(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\nstructure: true\nblueprints:\n  - page\nroute: /{slug}\ncontroller: " . BlogController::class . "::show\n");
        mkdir($this->site . 'collections/pages');
        mkdir($this->blueprints . 'collections/pages');
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
     * @return array<string, string> the id each old URI belongs to
     */
    private function redirects(): array
    {
        $file = $this->site . 'redirects.yaml';

        return is_file($file) ? Yaml::parseFile($file) : [];
    }

    private function uriOf(string $id): ?string
    {
        return content()->find($id)?->uri;
    }

    /**
     * @param list<array<string, mixed>> $tree
     */
    private function saveTree(array $tree): TestResponse
    {
        return $this->actingAs($this->admin)->post(ADMIN_AREA . '/content/pages/structure', [
            'tree' => json_encode($tree),
            'hash' => Structure::hash((string) file_get_contents($this->site . self::TREE)),
        ]);
    }

    private function assertNotFound(string $uri): void
    {
        $this->serve();

        try {
            $this->get($uri);
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail("{$uri} was served.");
    }

    private function assertNotFoundOnPost(string $uri): void
    {
        $this->serve();

        try {
            $this->post($uri);
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail("{$uri} was served.");
    }

    private function visit(string $uri): TestResponse
    {
        $this->serve();

        return $this->get($uri);
    }

    /**
     * Turns the fallback route on. With it on, the admin's posts in the same test would not find their routes,
     * so a test does those first.
     */
    private function serve(): void
    {
        config('Content')->autoRoute = true;
        $this->resetRouting();
    }

    private function resetRouting(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
    }
}
