<?php

namespace Tests\Content;

use Bonfire\Content\Entry;
use Bonfire\Content\EntryStore;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Content\ReindexResult;
use Bonfire\Content\Structure;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMXPath;
use Symfony\Component\Yaml\Yaml;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\SubmitsForms;

/**
 * Deleting entries to the trash, and restoring and purging them through the Recycler.
 *
 * @internal
 */
final class TrashTest extends ContentTestCase
{
    use SubmitsForms;

    private const FIRST     = '11111111-1111-4111-8111-111111111111';
    private const FIRST_MD  = 'collections/blog/2026-01-10.first-post.md';
    private const FIRST_BIN = '.trash/blog/2026-01-10.first-post.md';
    private const SECOND    = '22222222-2222-4222-8222-222222222222';
    private const HOME      = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1';
    private const ABOUT     = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa2';
    private const TEAM      = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa3';
    private const HISTORY   = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa4';
    private const JOBS      = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa5';
    private const LINKS     = '77777777-7777-4777-8777-777777777777';
    private const TREE_FILE = 'trees/collections/pages.yaml';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets', 'recycler'] as $service) {
            Services::resetSingle($service);
        }

        $this->writePages();
        $this->reindex();

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        foreach (['auth', 'bonfire', 'menus', 'widgets', 'recycler'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    // Deleting

    public function testDeleteMovesTheFileToTheTrashAndHidesTheEntry(): void
    {
        $this->trash('blog', self::FIRST)->assertRedirect();

        $this->assertFileDoesNotExist($this->site . self::FIRST_MD);
        $this->assertFileExists($this->site . self::FIRST_BIN);
        $this->assertNull(content()->find(self::FIRST));
        $this->assertSame(self::FIRST_MD, $this->row(self::FIRST)['path'], 'the row keeps the original path');

        $this->actingAs($this->admin)->get(route_to('content-list', 'blog'))->assertDontSee('First post');
    }

    public function testDeleteTouchesTheFile(): void
    {
        touch($this->site . self::FIRST_MD, 1_000_000);

        $this->trash('blog', self::FIRST);

        clearstatcache();
        $this->assertGreaterThan(1_000_000, filemtime($this->site . self::FIRST_BIN));
    }

    // Restoring and purging

    public function testTheRecyclerListsTrashedEntries(): void
    {
        $this->trash('blog', self::FIRST);

        $list = $this->actingAs($this->admin)->get(route_to('recycler') . '?r=entries');

        $list->assertOK();
        $list->assertSee('First post');
        $list->assertSee(route_to('recycler-restore', 'entries', self::FIRST));
    }

    public function testRestorePutsTheFileBackAndTheEntryReappears(): void
    {
        $this->trash('blog', self::FIRST);

        $this->restore(self::FIRST)->assertRedirect();

        $this->assertFileExists($this->site . self::FIRST_MD);
        $this->assertFileDoesNotExist($this->site . self::FIRST_BIN);
        $this->assertInstanceOf(Entry::class, content()->find(self::FIRST));
    }

    public function testPurgeRemovesTheFileAndTheRowAndItsTerms(): void
    {
        $this->useTags();
        $this->reindex();
        $this->trash('blog', self::FIRST);

        $this->assertSame(2, $this->pivotRows(self::FIRST), 'pivot rows stay while an entry is trashed');

        $this->purge(self::FIRST)->assertRedirect();

        $this->assertFileDoesNotExist($this->site . self::FIRST_BIN);
        $this->assertNull($this->row(self::FIRST));
        $this->assertSame(0, $this->pivotRows(self::FIRST));
    }

    public function testACollisionInTheTrashRefusesTheDeleteAndOverwritesNothing(): void
    {
        mkdir($this->site . '.trash/blog', 0775, true);
        $this->writeFile(self::FIRST_BIN, 'put there by hand');

        $this->trash('blog', self::FIRST)->assertSessionHas('error');

        $this->assertFileExists($this->site . self::FIRST_MD);
        $this->assertSame('put there by hand', file_get_contents($this->site . self::FIRST_BIN));
        $this->assertInstanceOf(Entry::class, content()->find(self::FIRST));
    }

    public function testDeleteNeedsThePermission(): void
    {
        $editor = $this->userWith(['admin.access', 'content.access', 'content.edit']);

        $list = $this->actingAs($editor)->get(route_to('content-list', 'blog'));
        $this->assertSame(0, $this->xpath($list->getBody())->query('//*[@data-delete]')->length);

        $edit = $this->actingAs($editor)->get(route_to('content-edit', 'blog', self::FIRST));
        $this->assertSame(0, $this->xpath($edit->getBody())->query('//*[@data-delete]')->length);

        $this->actingAs($editor)->post(route_to('content-delete', 'blog', self::FIRST), ['descendants' => 0])->assertRedirect();
        $this->assertFileExists($this->site . self::FIRST_MD);
        $this->assertInstanceOf(Entry::class, content()->find(self::FIRST));
    }

    public function testTheListAndTheFormOfferDeleteOutsideTheMainForm(): void
    {
        foreach ([route_to('content-list', 'pages'), route_to('content-edit', 'pages', self::ABOUT)] as $page) {
            $html   = $this->actingAs($this->admin)->get($page)->getBody();
            $action = url_to('content-delete', 'pages', self::ABOUT);
            $form   = $this->xpath($html)->query('//form[@action="' . $action . '"]')->item(0);

            $this->assertNotNull($form, $page);
            $this->assertFalse($form->hasAttribute('data-entry-form'));
            $this->assertSame('3', $this->formFields($html, $action)['descendants'], 'team, jobs and history');
            $this->assertSame(1, $this->xpath($html)->query('//form[@action="' . $action . '"]//button[@data-delete][@data-confirm][@data-confirm-danger="1"]')->length);
        }
    }

    public function testAStaleFormGetsTheConflictWhenItSaves(): void
    {
        $action = url_to('content-edit', 'blog', self::FIRST);
        $fields = $this->formFields($this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST))->getBody(), $action);

        $this->trash('blog', self::FIRST);

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::FIRST), $fields)->assertStatus(409);
        $this->assertFileDoesNotExist($this->site . self::FIRST_MD);
    }

    public function testATrashedEntryCantBeOpened(): void
    {
        $this->trash('blog', self::FIRST);

        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST));
    }

    public function testAStaleFormWithAnInvalidValueStillGetsTheConflictAndNoDelete(): void
    {
        $action = url_to('content-edit', 'blog', self::FIRST);
        $fields = $this->formFields($this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST))->getBody(), $action);

        $this->trash('blog', self::FIRST);
        $fields['fields']['title'] = '';

        $response = $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::FIRST), $fields);

        $response->assertStatus(409);
        $this->assertSame(0, $this->xpath($response->getBody())->query('//*[@data-delete]')->length);
    }

    public function testRestoreChecksTheNewUriOfALivePageBelowIt(): void
    {
        $this->routeBlog();
        $this->trash('pages', self::ABOUT, 3);

        // Back by hand, so history is live at /history under a trashed parent, and a post holds the URI it would get.
        rename($this->site . '.trash/pages/history.md', $this->site . 'collections/pages/history.md');
        $this->writeFile('collections/blog.yaml', "title: Blog\ndated: true\nsort: published_at desc\nroute: /about/{slug}\nblueprints:\n  - post\n");
        $this->writeFile('collections/blog/2026-06-06.history.md', "---\nid: bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb2\ntitle: History post\n---\n");
        $this->reindex();

        $this->restore(self::ABOUT);

        $this->assertStringContainsString('History post', implode(' ', (array) session('error')));
        $this->assertSame('/history', content()->find(self::HISTORY)->uri);
        $this->assertNull(content()->find(self::ABOUT));
    }

    // Subtrees

    public function testDeletingAParentTrashesItsSubtree(): void
    {
        $before = file_get_contents($this->site . self::TREE_FILE);

        $this->trash('pages', self::ABOUT, 3)->assertRedirect();

        foreach (['about', 'team', 'jobs', 'history'] as $slug) {
            $this->assertFileExists($this->site . ".trash/pages/{$slug}.md");
            $this->assertFileDoesNotExist($this->site . "collections/pages/{$slug}.md");
        }

        $this->assertSame([self::HOME], array_map(static fn ($entry) => $entry->id, content()->collection('pages')->get()));
        $this->assertSame($before, file_get_contents($this->site . self::TREE_FILE), 'a delete leaves the tree file alone');
    }

    public function testADeleteWithAnOutOfDateCountIsRefused(): void
    {
        $this->trash('pages', self::ABOUT, 2)->assertSessionHas('error');

        $this->assertFileExists($this->site . 'collections/pages/about.md');
        $this->assertInstanceOf(Entry::class, content()->find(self::TEAM));
    }

    public function testAMoveThatFailsPutsBackWhatItMoved(): void
    {
        // A folder where a file should go can't be renamed over.
        mkdir($this->site . '.trash/pages/team.md', 0775, true);

        $this->trash('pages', self::ABOUT, 3)->assertSessionHas('error');

        $this->assertFileExists($this->site . 'collections/pages/about.md');
        $this->assertFileExists($this->site . 'collections/pages/team.md');
        $this->assertFileDoesNotExist($this->site . '.trash/pages/about.md');
        $this->assertInstanceOf(Entry::class, content()->find(self::ABOUT));

        rmdir($this->site . '.trash/pages/team.md');
    }

    public function testASiblingsSortFollowsLiveEntriesOnly(): void
    {
        $this->trash('pages', self::TEAM, 1);

        $this->assertSame(0, (int) $this->row(self::HISTORY)['sort']);
        $this->assertSame(0, $this->reindex()->updated, 'the same as a reindex would say');
    }

    // Restoring a group

    public function testRestoringAParentBringsBackItsSubtreeInItsExactPlace(): void
    {
        $tree = file_get_contents($this->site . self::TREE_FILE);

        // jobs goes first, on its own.
        $this->trash('pages', self::JOBS);
        $this->trash('pages', self::ABOUT, 2);

        $this->restore(self::ABOUT)->assertSessionMissing('error');

        foreach ([self::ABOUT, self::TEAM, self::JOBS, self::HISTORY] as $id) {
            $this->assertInstanceOf(Entry::class, content()->find($id));
        }

        $this->assertSame('/about/team/jobs', content()->find(self::JOBS)->uri);
        $this->assertSame(1, (int) $this->row(self::HISTORY)['sort']);
        $this->assertSame($tree, file_get_contents($this->site . self::TREE_FILE));
    }

    public function testRestoringAChildUnderATrashedParentIsRefusedNamingTheParent(): void
    {
        $this->trash('pages', self::ABOUT, 3);

        $this->restore(self::TEAM);

        $this->assertStringContainsString('About', implode(' ', (array) session('error')));
        $this->assertNull(content()->find(self::TEAM));
        $this->assertFileExists($this->site . '.trash/pages/team.md');
    }

    public function testRestoreIsAllOrNothingWhenAPathIsTaken(): void
    {
        $this->trash('pages', self::ABOUT, 3);
        $this->writeFile('collections/pages/jobs.md', "---\nid: bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb1\ntitle: By hand\n---\n");

        $this->restore(self::ABOUT)->assertSessionHas('error');

        $this->assertStringContainsString('By hand', (string) file_get_contents($this->site . 'collections/pages/jobs.md'));
        $this->assertFileDoesNotExist($this->site . 'collections/pages/about.md');
        $this->assertNull(content()->find(self::ABOUT));
    }

    public function testRestoreIsRefusedWhenAUriIsTakenNamingTheBlocker(): void
    {
        $this->routeBlog();
        $this->trash('blog', self::FIRST);

        // A page takes the URI the post held.
        $this->writeFile('collections/pages/first-post.md', "---\nid: bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb1\ntitle: Blocker\n---\n");
        $this->reindex();

        $this->restore(self::FIRST);

        $this->assertStringContainsString('Blocker', implode(' ', (array) session('error')));
        $this->assertFileExists($this->site . self::FIRST_BIN);
        $this->assertNull(content()->find(self::FIRST));
    }

    public function testRestoreIsRefusedWhenTheTrashFileIsMissing(): void
    {
        $this->trash('blog', self::FIRST);
        unlink($this->site . self::FIRST_BIN);

        $this->restore(self::FIRST)->assertSessionHas('error');

        $this->assertNull(content()->find(self::FIRST));
    }

    public function testRecyclerViewAloneCanRestoreAndPurge(): void
    {
        $this->trash('blog', self::FIRST);
        $this->trash('blog', self::SECOND);
        $user = $this->userWith(['admin.access', 'recycler.view']);

        $this->actingAs($user)->get(route_to('recycler-restore', 'entries', self::FIRST));
        $this->assertInstanceOf(Entry::class, content()->find(self::FIRST));

        $this->actingAs($user)->get(route_to('recycler-purge', 'entries', self::SECOND));
        $this->assertNull($this->row(self::SECOND));
    }

    // Purging in a tree

    public function testPurgingAParentLiftsItsTrashedChildrenWhichStayRestorable(): void
    {
        $this->trash('pages', self::ABOUT, 3);

        $this->purge(self::ABOUT)->assertSessionMissing('error');

        $this->assertNull($this->row(self::ABOUT));
        $this->assertSame(
            [['id' => self::HOME], ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]], ['id' => self::HISTORY]],
            Yaml::parseFile($this->site . self::TREE_FILE),
        );

        $this->restore(self::TEAM)->assertSessionMissing('error');

        $this->assertSame('/team', content()->find(self::TEAM)->uri);
        $this->assertSame('/team/jobs', content()->find(self::JOBS)->uri);
        $this->assertNull(content()->find(self::HISTORY));
    }

    public function testPurgeJustClearsTheRowWhenTheTrashFileIsGone(): void
    {
        $this->trash('blog', self::FIRST);
        unlink($this->site . self::FIRST_BIN);

        $this->purge(self::FIRST)->assertSessionMissing('error');

        $this->assertNull($this->row(self::FIRST));
    }

    public function testAnEntryOutOfTheTrashCantBeRestoredOrPurged(): void
    {
        $this->restore(self::FIRST)->assertSessionHas('error');
        $this->purge(self::FIRST)->assertSessionHas('error');

        $this->assertFileExists($this->site . self::FIRST_MD);
        $this->assertInstanceOf(Entry::class, content()->find(self::FIRST));
    }

    // Slugs and URIs stay reserved

    public function testASlugInTheTrashIsReserved(): void
    {
        $this->trash('pages', self::HOME);

        $response = $this->newPage('Home', 'home');

        $this->assertSame(1, $this->xpath($response->getBody())->query('//*[@data-notice][contains(., "' . EntryStore::TRASH_HOLDS_SLUG . '")]')->length);
        $this->assertFileDoesNotExist($this->site . 'collections/pages/home.md');
    }

    public function testATrashedUriIsReservedAgainstANewEntry(): void
    {
        $this->routeBlog();
        $this->trash('blog', self::FIRST);

        $response = $this->newPage('First', 'first-post');

        $this->assertSame(1, $this->xpath($response->getBody())->query('//*[@data-notice][contains(., "' . EntryStore::TRASH_HOLDS_SLUG . '")]')->length);
        $this->assertFileDoesNotExist($this->site . 'collections/pages/first-post.md');
    }

    public function testATrashedUriIsReservedAgainstAPageThatMovesThere(): void
    {
        $this->routeBlog();
        $this->writeFile('collections/blog/2026-06-06.team.md', "---\nid: bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb2\ntitle: Team post\n---\n");
        $this->reindex();
        $this->trash('blog', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb2');

        $response = $this->saveTree([['id' => self::HOME], ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]], ['id' => self::ABOUT, 'children' => [['id' => self::HISTORY]]]]);

        $response->assertStatus(422);
        $this->assertSame('/about/team', content()->find(self::TEAM)->uri);
    }

    // The tree editor

    public function testTreeWritesKeepTrashedIds(): void
    {
        $this->trash('pages', self::HISTORY);

        // A new page rewrites the tree.
        $this->newPage('New', 'new');

        $this->assertStringContainsString(self::HISTORY, (string) file_get_contents($this->site . self::TREE_FILE));

        $this->saveTree([['id' => self::HOME], ['id' => self::ABOUT, 'children' => [['id' => self::HISTORY], ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]]]]])->assertRedirect();

        $this->assertStringContainsString(self::HISTORY, (string) file_get_contents($this->site . self::TREE_FILE));

        $this->restore(self::HISTORY);

        $this->assertSame('/about/history', content()->find(self::HISTORY)->uri);
        $this->assertSame(0, (int) $this->row(self::HISTORY)['sort'], 'it comes back at the spot it was moved to');
    }

    public function testTheEditorMarksTrashedPagesAndOffersTheToggleOnlyWhenThereAreSome(): void
    {
        $path = $this->xpath($this->actingAs($this->admin)->get(route_to('content-structure', 'pages'))->getBody());

        $this->assertSame(0, $path->query('//*[@data-toggle-trashed]')->length);

        $this->trash('pages', self::TEAM, 1);

        $path = $this->xpath($this->actingAs($this->admin)->get(route_to('content-structure', 'pages'))->getBody());

        $this->assertSame(1, $path->query('//*[@data-toggle-trashed]')->length);
        $this->assertSame(1, $path->query('//li[@data-id="' . self::TEAM . '"][@data-trashed]//li[@data-id="' . self::JOBS . '"][@data-trashed]')->length, 'they stay in the page, where they are in the tree');
        $this->assertSame(0, $path->query('//li[@data-id="' . self::HOME . '"][@data-trashed]')->length);
    }

    public function testALivePageUnderATrashedOneIsRefused(): void
    {
        $this->trash('pages', self::HISTORY);
        $before = file_get_contents($this->site . self::TREE_FILE);

        $response = $this->saveTree([['id' => self::ABOUT, 'children' => [['id' => self::HISTORY, 'children' => [['id' => self::HOME]]], ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]]]]]);

        $response->assertStatus(422);
        $this->assertSame($before, file_get_contents($this->site . self::TREE_FILE));
    }

    public function testTrashedRowsCountTowardMaxDepth(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\nstructure: true\nmax_depth: 2\nroute: /{slug}\nblueprints:\n  - page\n");
        $this->trash('pages', self::JOBS);

        // Deeper than two only through the trashed jobs.
        $this->saveTree([['id' => self::HOME], ['id' => self::ABOUT, 'children' => [['id' => self::TEAM, 'children' => [['id' => self::JOBS]]], ['id' => self::HISTORY]]]])->assertStatus(422);
    }

    // Reindex: the file's location decides

    public function testAFileOnlyInTheTrashMakesTheRowTrashedEvenIfItWasLive(): void
    {
        mkdir($this->site . '.trash/blog', 0775, true);
        rename($this->site . self::FIRST_MD, $this->site . self::FIRST_BIN);

        $this->assertSame(1, $this->reindex()->updated);

        $this->assertNull(content()->find(self::FIRST));
        $this->assertNotNull($this->row(self::FIRST)['deleted_at']);
        $this->assertSame(self::FIRST_MD, $this->row(self::FIRST)['path']);
    }

    public function testAFileOnlyInCollectionsMakesTheRowLiveEvenIfItWasTrashed(): void
    {
        $this->trash('blog', self::FIRST);
        rename($this->site . self::FIRST_BIN, $this->site . self::FIRST_MD);

        $this->reindex();

        $this->assertInstanceOf(Entry::class, content()->find(self::FIRST));
    }

    public function testTheSameIdInBothKeepsTheLiveOneAndReportsTheTrashCopy(): void
    {
        mkdir($this->site . '.trash/blog', 0775, true);
        copy($this->site . self::FIRST_MD, $this->site . self::FIRST_BIN);

        $result = $this->reindex();

        $this->assertArrayHasKey(self::FIRST_BIN, $result->errors);
        $this->assertInstanceOf(Entry::class, content()->find(self::FIRST));
        $this->assertFileExists($this->site . self::FIRST_BIN);
    }

    public function testAFileInNeitherRemovesTheRow(): void
    {
        $this->trash('blog', self::FIRST);
        unlink($this->site . self::FIRST_BIN);

        $this->assertSame(1, $this->reindex()->removed);

        $this->assertNull($this->row(self::FIRST));
    }

    public function testATrashFileWhoseConfigIsGoneIsReportedAndComesBackWithTheConfig(): void
    {
        $this->trash('blog', self::FIRST);
        $config = file_get_contents($this->site . 'collections/blog.yaml');
        unlink($this->site . 'collections/blog.yaml');

        $result = $this->reindex();

        $this->assertArrayHasKey(self::FIRST_BIN, $result->errors);
        $this->assertNull($this->row(self::FIRST));
        $this->assertFileExists($this->site . self::FIRST_BIN);

        $this->writeFile('collections/blog.yaml', $config);
        $this->assertSame([], $this->reindex()->errors);

        $this->assertNotNull($this->row(self::FIRST)['deleted_at']);
    }

    public function testAFreshReindexKeepsTrashedEntriesTrashedDatedByTheFile(): void
    {
        $this->useTags();
        $this->reindex();
        $this->trash('blog', self::FIRST);
        touch($this->site . self::FIRST_BIN, 1_500_000_000);

        $this->assertSame([], $this->reindex(true)->errors);

        $this->assertNull(content()->find(self::FIRST));
        $this->assertSame(date('Y-m-d H:i:s', 1_500_000_000), $this->row(self::FIRST)['deleted_at']);
        $this->assertSame(2, $this->pivotRows(self::FIRST));
    }

    public function testAnIncrementalReindexKeepsTheDateAndChangesNothing(): void
    {
        $this->trash('pages', self::ABOUT, 3);
        $date = '2020-02-02 02:02:02';
        (new ContentEntryModel())->update(self::ABOUT, ['deleted_at' => $date]);

        $result = $this->reindex();

        $this->assertSame(0, $result->added + $result->updated + $result->removed);
        $this->assertSame($date, $this->row(self::ABOUT)['deleted_at']);
    }

    public function testAFreshReindexKeepsTheReservationOfATrashedPage(): void
    {
        $this->trash('pages', self::ABOUT, 3);

        $this->reindex(true);

        $this->assertSame('/about/team/jobs', $this->row(self::JOBS)['uri']);
        $this->assertSame(self::TEAM, $this->row(self::JOBS)['parent_id']);

        $this->restore(self::ABOUT)->assertSessionMissing('error');
        $this->assertSame('/about/team', content()->find(self::TEAM)->uri);
    }

    public function testATrashedFileCantPushOutALiveEntry(): void
    {
        $this->routeBlog();
        $this->trash('blog', self::FIRST);
        // Sorts before everything, and has the URI the trashed post holds.
        $this->writeFile('collections/pages/first-post.md', "---\nid: bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb1\ntitle: Live\n---\n");

        $result = $this->reindex(true);

        $this->assertSame([], $result->errors);
        $this->assertSame('Live', content()->findByUri('/first-post')->title);
        $this->assertNotNull($this->row(self::FIRST)['deleted_at'], 'it stays restorable once the live one is gone');
    }

    public function testAPageMovedUpByAPurgeOntoALiveUriSurvivesAReindexAndItsRestoreIsRefused(): void
    {
        $this->trash('pages', self::ABOUT, 3);
        $this->writeFile('collections/blog.yaml', "title: Blog\ndated: true\nsort: published_at desc\nroute: /{slug}\nblueprints:\n  - post\n");
        $this->writeFile('collections/blog/2026-06-06.team.md', "---\nid: bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb2\ntitle: Team post\n---\n");
        $this->reindex();

        $this->purge(self::ABOUT)->assertSessionMissing('error');

        $this->assertSame([], $this->reindex(true)->errors);
        $this->assertNotNull($this->row(self::TEAM));

        $this->restore(self::TEAM);

        $this->assertStringContainsString('Team post', implode(' ', (array) session('error')));
        $this->assertNull(content()->find(self::TEAM));
    }

    // entries links

    public function testASavedFormKeepsALinkToATrashedEntryAndItWorksAfterRestore(): void
    {
        $this->useLinks('    max_items: 3');
        $this->trash('pages', self::HOME);

        $action = url_to('content-edit', 'blog', self::LINKS);
        $html   = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::LINKS))->getBody();
        $path   = $this->xpath($html);

        $this->assertSame(1, $path->query('//input[@name="fields[related][]"][@value="' . self::HOME . '"][@checked]')->length);
        $this->assertSame(1, $path->query('//*[@data-trashed-link="' . self::HOME . '"]')->length);

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::LINKS), $this->formFields($html, $action))->assertRedirect();

        $this->assertEqualsCanonicalizing([self::HOME, self::ABOUT], content()->find(self::LINKS)->data['related']);
        $this->assertSame([self::ABOUT], array_map(static fn ($entry) => $entry->id, content()->find(self::LINKS)->entries('related')), 'skipped while it is trashed');

        $this->restore(self::HOME);

        $this->assertEqualsCanonicalizing([self::HOME, self::ABOUT], array_map(static fn ($entry) => $entry->id, content()->find(self::LINKS)->entries('related')));
    }

    public function testADropdownKeepsALinkToATrashedEntry(): void
    {
        $this->useLinks('    max_items: 1', [self::HOME]);
        $this->trash('pages', self::HOME);

        $path = $this->xpath($this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::LINKS))->getBody());

        $this->assertSame(1, $path->query('//select[@name="fields[related][]"]/option[@value="' . self::HOME . '"][@selected][@data-trashed-link]')->length);
    }

    public function testATrashedEntryIsOnlyShownToTheEntryLinkingToIt(): void
    {
        $this->useLinks('    max_items: 3', [self::ABOUT]);
        $this->trash('pages', self::HOME);

        $path = $this->xpath($this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::LINKS))->getBody());

        $this->assertSame(0, $path->query('//input[@value="' . self::HOME . '"]')->length);
    }

    /**
     * Gives the blog a `related` entries field of pages, and a post that links to these.
     *
     * @param list<string> $links
     */
    private function useLinks(string $options, array $links = [self::HOME, self::ABOUT]): void
    {
        file_put_contents($this->blueprints . 'collections/blog/post.yaml', "  - handle: related\n    type: entries\n    collections: [pages]\n{$options}\n", FILE_APPEND);
        $this->writeFile('collections/blog/2026-05-05.links.md', "---\nid: " . self::LINKS . "\ntitle: Links\nrelated:\n  - " . implode("\n  - ", $links) . "\n---\nBody.\n");
        $this->reindex();
    }

    private function newPage(string $title, string $slug): TestResponse
    {
        return $this->actingAs($this->admin)->post(route_to('content-new', 'pages'), ['blueprint' => 'page', 'hash' => '', 'status' => 'draft', 'fields' => ['title' => $title, 'slug' => $slug]]);
    }

    private function routeBlog(): void
    {
        $this->reindexBlogWith("sort: published_at desc\nroute: /{slug}\nblueprints:\n  - post\n");
    }

    /**
     * Posts a tree the way the editor does.
     *
     * @param list<array<string, mixed>> $tree
     */
    private function saveTree(array $tree): TestResponse
    {
        return $this->actingAs($this->admin)->post(route_to('content-structure', 'pages'), [
            'tree' => json_encode($tree),
            'hash' => Structure::hash((string) file_get_contents($this->site . self::TREE_FILE)),
        ]);
    }

    private function userWith(array $permissions): User
    {
        $user = $this->createUser();

        foreach ($permissions as $permission) {
            $user->addPermission($permission);
        }

        return $user;
    }

    private function trash(string $handle, string $id, int $descendants = 0): TestResponse
    {
        return $this->actingAs($this->admin)->post(route_to('content-delete', $handle, $id), ['descendants' => $descendants]);
    }

    private function restore(string $id): TestResponse
    {
        return $this->actingAs($this->admin)->get(route_to('recycler-restore', 'entries', $id));
    }

    private function purge(string $id): TestResponse
    {
        return $this->actingAs($this->admin)->get(route_to('recycler-purge', 'entries', $id));
    }

    /**
     * @return array<string, mixed>|null the row, trashed or not
     */
    private function row(string $id): ?array
    {
        return (new ContentEntryModel())->withDeleted()->find($id);
    }

    private function pivotRows(string $id): int
    {
        return db_connect()->table('content_entry_terms')->where('entry_id', $id)->countAllResults();
    }

    private function reindex(bool $fresh = false): ReindexResult
    {
        return (new Indexer($this->site))->reindex($fresh);
    }

    /**
     * A pages collection: home, then about with team (with jobs below it) and history below it.
     */
    private function writePages(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\nstructure: true\nroute: /{slug}\nblueprints:\n  - page\n");
        mkdir($this->site . 'collections/pages');
        mkdir($this->blueprints . 'collections/pages');
        file_put_contents($this->blueprints . 'collections/pages/page.yaml', "title: Page\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n");

        foreach ([self::HOME => 'home', self::ABOUT => 'about', self::TEAM => 'team', self::HISTORY => 'history', self::JOBS => 'jobs'] as $id => $slug) {
            $this->writeFile("collections/pages/{$slug}.md", "---\nid: {$id}\ntitle: " . ucfirst($slug) . "\n---\nBody of {$slug}.\n");
        }

        mkdir($this->site . 'trees/collections', 0775, true);
        $this->writeFile(self::TREE_FILE, Yaml::dump([
            ['id' => self::HOME],
            ['id' => self::ABOUT, 'children' => [
                ['id' => self::TEAM, 'children' => [['id' => self::JOBS]]],
                ['id' => self::HISTORY],
            ]],
        ], 10, 2));
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
