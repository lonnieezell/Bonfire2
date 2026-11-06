<?php

namespace Tests\Content;

use Bonfire\Content\Collection;
use Bonfire\Content\Entry;
use Bonfire\Content\EntryParser;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\SubmitsForms;

/**
 * @internal
 */
final class EntryAdminTest extends ContentTestCase
{
    use SubmitsForms;

    private const FIRST = '11111111-1111-4111-8111-111111111111';
    private const DRAFT = '33333333-3333-4333-8333-333333333333';

    private User $admin;

    /**
     * The parsed pages query() has been asked about, with their responses so the ids stay theirs.
     *
     * @var array<int, array{TestResponse, DOMXPath}>
     */
    private array $pages = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->pages = [];
        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        (new Indexer($this->site))->reindex();

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

    // Permissions and the menu

    public function testSuperadminAndAdminHoldEveryContentPermission(): void
    {
        $admin = $this->createUser();
        $admin->addGroup('admin');

        foreach ([$this->admin, $admin] as $user) {
            foreach (['access', 'edit', 'publish', 'delete', 'structure'] as $permission) {
                $this->assertTrue($user->can("content.{$permission}"), "content.{$permission}");
            }
        }
    }

    public function testTheMenuHasAnItemForEachCollection(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\n");

        $response = $this->actingAs($this->admin)->get(ADMIN_AREA);

        $response->assertOK();
        $this->assertStringContainsString(route_to('content-list', 'blog'), $response->getBody());
        $this->assertStringContainsString(route_to('content-list', 'pages'), $response->getBody());
    }

    public function testWithoutContentAccessThereIsNoMenuAndTheRoutesDeny(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.edit', 'content.publish');

        $dashboard = $this->actingAs($user)->get(ADMIN_AREA);
        $dashboard->assertOK();
        $this->assertStringNotContainsString(route_to('content-list', 'blog'), $dashboard->getBody());

        $before = file_get_contents($this->site . 'collections/blog/2026-01-10.first-post.md');
        $paths  = [
            ['get', route_to('content-list', 'blog')],
            ['get', route_to('content-new', 'blog')],
            ['post', route_to('content-new', 'blog')],
            ['get', route_to('content-edit', 'blog', self::FIRST)],
            ['post', route_to('content-edit', 'blog', self::FIRST)],
            ['post', route_to('content-preview')],
        ];

        foreach ($paths as [$method, $path]) {
            $response = $this->actingAs($user)->withHeaders(['HX-Request' => 'true'])->{$method}($path, ['fields' => ['title' => 'Hacked']]);

            $response->assertRedirectTo(site_url(ADMIN_AREA));
            $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
        }

        $this->assertSame($before, file_get_contents($this->site . 'collections/blog/2026-01-10.first-post.md'));
        $this->assertSame(4, (new ContentEntryModel())->countAllResults());
    }

    public function testWithContentAccessButNotEditTheListShowsAndTheFormsDeny(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access');

        $list = $this->actingAs($user)->get(route_to('content-list', 'blog'));

        $list->assertOK();
        $this->assertCount(4, $this->query($list, '//tr[@data-entry]'));
        $this->assertCount(0, $this->query($list, '//a[@data-new]'));
        $this->assertCount(0, $this->query($list, '//td[@data-title]/a'));

        $this->actingAs($user)->get(route_to('content-new', 'blog'))->assertRedirectTo(site_url(ADMIN_AREA));
        $this->actingAs($user)->get(route_to('content-edit', 'blog', self::FIRST))->assertRedirectTo(site_url(ADMIN_AREA));
    }

    // The list

    public function testTheListShowsTitleStatusAndDateOfEachEntry(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-list', 'blog'));

        $response->assertOK();

        $rows = [];

        foreach ($this->query($response, '//tr[@data-entry]') as $row) {
            $rows[$row->getAttribute('data-entry')] = [
                trim($this->query($response, './/td[@data-title]', $row)->item(0)->textContent),
                $this->query($response, './/td[@data-status]', $row)->item(0)->getAttribute('data-status'),
                trim($this->query($response, './/td[@data-date]', $row)->item(0)->textContent) !== '',
            ];
        }

        $this->assertSame(['First post', 'published', true], $rows[self::FIRST]);
        $this->assertSame('draft', $rows[self::DRAFT][1]);
        $this->assertCount(4, $rows);
    }

    public function testTheListIsPaginatedFromTheIndex(): void
    {
        setting('Site.perPage', 3);

        $response = $this->actingAs($this->admin)->get(route_to('content-list', 'blog'));

        $this->assertCount(3, $this->query($response, '//tr[@data-entry]'));
        $this->assertCount(1, $this->query($response, '//a[contains(@href, "page=2")]'));
    }

    public function testAnUnknownCollectionIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('content-list', 'nope'));
    }

    // Creating

    public function testCreatingAnEntryWritesItsFileAndTheIndexRowMatchesIt(): void
    {
        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint'    => 'post',
            'fields'       => ['title' => 'Brand New', 'tagline' => 'Hello', 'featured' => '1', 'body' => "Some *Markdown*.\n"],
            'status'       => 'published',
            'published_at' => '2026-04-01T09:30',
        ]);

        $row = (new ContentEntryModel())->where('slug', 'brand-new')->first();

        $this->assertNotNull($row);
        $response->assertRedirectTo(site_url(route_to('content-edit', 'blog', $row['id'])));
        $this->assertSame('collections/blog/2026-04-01.brand-new.md', $row['path']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/', $row['id']);

        $raw = file_get_contents($this->site . $row['path']);
        $this->assertStringStartsWith("---\nid: {$row['id']}\n", $raw);
        $this->assertStringEndsWith("---\nSome *Markdown*.\n", $raw);

        $record = EntryParser::parse(Collection::load($this->site, 'blog'), $raw, basename($row['path']));

        foreach ($record as $column => $value) {
            $this->assertSame((string) $value, (string) $row[$column], $column);
        }

        $entry = Entry::fromRow($row);
        $this->assertSame('Brand New', $entry->title);
        $this->assertSame('published', $entry->status);
        $this->assertSame('2026-04-01 09:30:00', $entry->publishedAt);
        $this->assertSame('Hello', $entry->field('tagline'));
        $this->assertTrue($entry->field('featured'));
        $this->assertSame("Some *Markdown*.\n", $entry->body);

        // The index agrees with a rebuild from the files.
        $result = (new Indexer($this->site))->reindex();
        $this->assertSame([0, 0, 0, 5], [$result->added, $result->updated, $result->removed, $result->unchanged]);
    }

    public function testANewEntryGetsItsSlugFromTheTitleAndDefaultsToDraftToday(): void
    {
        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Déjà Vu & Friends!']])->assertRedirect();

        $row = (new ContentEntryModel())->where('title', 'Déjà Vu & Friends!')->first();

        $this->assertNotNull($row);
        $this->assertSame('déjà-vu-friends', $row['slug']);
        $this->assertSame('draft', $row['status']);
        $this->assertSame('collections/blog/' . date('Y-m-d') . '.déjà-vu-friends.md', $row['path']);
    }

    public function testATypedSlugNamesTheFile(): void
    {
        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Whatever', 'slug' => 'My Own Slug']])->assertRedirect();

        $this->assertNotNull((new ContentEntryModel())->where('slug', 'my-own-slug')->first());
    }

    public function testAnEntryInAnUndatedCollectionHasNoDatePrefix(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\nblueprints:\n  - page\n");
        mkdir($this->blueprints . 'collections/pages');
        file_put_contents($this->blueprints . 'collections/pages/page.yaml', "fields:\n  - handle: title\n    type: text\n    validate: required\n");

        $this->actingAs($this->admin)->post(route_to('content-new', 'pages'), ['fields' => ['title' => 'About us']])->assertRedirect();

        $row = (new ContentEntryModel())->where('collection', 'pages')->first();

        $this->assertSame('collections/pages/about-us.md', $row['path']);
    }

    public function testASlugThatIsTakenIsRefusedAndNothingIsOverwritten(): void
    {
        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Dup', 'body' => 'one'], 'published_at' => '2026-05-05T00:00'])->assertRedirect();
        $path   = $this->site . 'collections/blog/2026-05-05.dup.md';
        $before = file_get_contents($path);

        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Dup', 'body' => 'two'], 'published_at' => '2026-05-05T00:00']);

        $response->assertOK();
        $this->assertCount(1, $this->query($response, '//*[@data-notice]'));
        $this->assertSame($before, file_get_contents($path));
        $this->assertSame(1, (new ContentEntryModel())->where('slug', 'dup')->countAllResults());
    }

    public function testATitleThatMakesNoSlugIsRefusedWithoutWritingAFile(): void
    {
        $before = glob($this->site . 'collections/blog/*');

        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => '!!! ???']]);

        $response->assertOK();
        $this->assertCount(1, $this->query($response, '//*[@data-error="slug"]'));
        $this->assertSame($before, glob($this->site . 'collections/blog/*'));
    }

    public function testWithoutASlugFieldATitleThatMakesNoSlugIsRefused(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\nblueprints:\n  - page\n");
        mkdir($this->blueprints . 'collections/pages');
        file_put_contents($this->blueprints . 'collections/pages/page.yaml', "fields:\n  - handle: title\n    type: text\n");

        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'pages'), ['fields' => ['title' => '']]);

        $response->assertOK();
        $this->assertCount(1, $this->query($response, '//*[@data-notice]'));
        $this->assertSame([], glob($this->site . 'collections/pages/*'));
    }

    public function testAStaleIndexRowForADeletedFileBlocksANewEntryAtItsPath(): void
    {
        unlink($this->site . 'collections/blog/2026-01-10.first-post.md');

        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'First post'], 'published_at' => '2026-01-10T00:00']);

        $response->assertOK();
        $this->assertCount(1, $this->query($response, '//*[@data-notice]'));
        $this->assertSame(1, (new ContentEntryModel())->where('path', 'collections/blog/2026-01-10.first-post.md')->countAllResults());
    }

    public function testThePublishDateOfAnEntryInADatedCollectionCannotBeCleared(): void
    {
        $path   = $this->site . 'collections/blog/2026-01-10.first-post.md';
        $before = file_get_contents($path);

        $form   = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST));
        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::FIRST));

        $fields['published_at'] = '';

        $response = $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::FIRST), $fields);

        $response->assertOK();
        $this->assertCount(1, $this->query($response, '//*[@data-error="published_at"]'));
        $this->assertSame($before, file_get_contents($path));
    }

    public function testNoTemporaryFileIsLeftBehind(): void
    {
        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Tidy']])->assertRedirect();

        $this->assertSame([], glob($this->site . 'collections/blog/.entry-*'));
    }

    // Editing

    public function testEditingRewritesTheFileAndKeepsTheIdAndBody(): void
    {
        $path = $this->site . 'collections/blog/2026-01-10.first-post.md';

        $form = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST));
        $form->assertOK();

        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::FIRST));
        $this->assertSame('First post', $fields['fields']['title']);

        $fields['fields']['title'] = 'First post, revised';

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::FIRST), $fields)->assertRedirect();

        $record = EntryParser::parse(Collection::load($this->site, 'blog'), file_get_contents($path), basename($path));

        $this->assertSame(self::FIRST, $record['id']);
        $this->assertSame('First post, revised', $record['title']);
        $this->assertSame("The first *post*.\n", $record['body']);
        $this->assertSame('Where it starts', json_decode($record['data'], true)['tagline']);

        $row = (new ContentEntryModel())->find(self::FIRST);
        $this->assertSame('First post, revised', $row['title']);
        $this->assertSame($record['hash'], $row['hash']);
        $this->assertSame('collections/blog/2026-01-10.first-post.md', $row['path']);
    }

    public function testAFrontMatterRoundTripKeepsEveryValue(): void
    {
        $original = <<<'MD'
            ---
            # A comment, which a save drops.
            id: 44444444-4444-4444-8444-444444444444
            title: 'Round: trip'
            status: published
            published_at: '2026-02-02 08:00:00'
            tagline: "Tab\there"
            summary: |
              Line one
                indented line two

              after a gap
            featured: true
            rating: 4.5
            count: 7
            nothing: null
            quoted: '2026-10-27'
            unquoted: 2026-10-28
            tags:
              - php
              - 'yaml: 1'
            meta:
              author:
                name: Ada
              flags: [1, 2]
            ---

            Body that starts with a blank line,
            and keeps    its spacing.

            MD;
        $this->writeFile('collections/blog/2026-02-02.round-trip.md', $original);
        (new Indexer($this->site))->reindex();

        $collection = Collection::load($this->site, 'blog');
        $before     = json_decode(EntryParser::parse($collection, $original, '2026-02-02.round-trip.md')['data'], true);
        $id         = $before['id'];

        $form   = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', $id));
        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', $id));

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', $id), $fields)->assertRedirect();

        $raw    = file_get_contents($this->site . 'collections/blog/2026-02-02.round-trip.md');
        $record = EntryParser::parse($collection, $raw, '2026-02-02.round-trip.md');

        $this->assertSame($before, json_decode($record['data'], true));
        $this->assertStringNotContainsString('A comment', (string) $raw);
    }

    public function testASaveAfterTheFileChangedOnDiskIsRefusedAndLeavesTheFileAlone(): void
    {
        $path = $this->site . 'collections/blog/2026-01-10.first-post.md';

        $form   = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST));
        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::FIRST));

        $theirs = "---\nid: " . self::FIRST . "\ntitle: Somebody else's title\n---\nTheir body.\n";
        file_put_contents($path, $theirs);

        $fields['fields']['title'] = 'My title';
        $response                  = $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::FIRST), $fields);

        $response->assertStatus(409);
        $this->assertCount(1, $this->query($response, '//*[@data-notice]//a[@data-reload]'));
        $this->assertSame($theirs, file_get_contents($path));
        $this->assertSame('First post', (new ContentEntryModel())->find(self::FIRST)['title']);
        // The input is kept, so it isn't lost.
        $this->assertSame('My title', $this->query($response, '//input[@name="fields[title]"]')->item(0)->getAttribute('value'));
    }

    public function testASaveWithoutTheHashIsRefused(): void
    {
        $path   = $this->site . 'collections/blog/2026-01-10.first-post.md';
        $before = file_get_contents($path);

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::FIRST), ['fields' => ['title' => 'No hash']])->assertStatus(409);

        $this->assertSame($before, file_get_contents($path));
    }

    public function testTheFormShowsTheFileNotAStaleIndex(): void
    {
        $path = $this->site . 'collections/blog/2026-01-10.first-post.md';
        file_put_contents($path, "---\nid: " . self::FIRST . "\ntitle: Changed outside\n---\nBody.\n");

        $form   = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST));
        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::FIRST));

        $this->assertSame('Changed outside', $fields['fields']['title']);

        $fields['fields']['title'] = 'Then edited';
        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::FIRST), $fields)->assertRedirect();

        $this->assertSame('Then edited', (new ContentEntryModel())->find(self::FIRST)['title']);
    }

    public function testTheSlugOfAnExistingEntryCannotBeChanged(): void
    {
        $form   = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST));
        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::FIRST));

        $this->assertArrayNotHasKey('slug', $fields['fields'], 'the slug input is disabled');

        $fields['fields']['slug'] = 'renamed';
        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::FIRST), $fields)->assertRedirect();

        $row = (new ContentEntryModel())->find(self::FIRST);
        $this->assertSame('first-post', $row['slug']);
        $this->assertFileExists($this->site . 'collections/blog/2026-01-10.first-post.md');
        $this->assertStringNotContainsString('renamed', (string) file_get_contents($this->site . $row['path']));
    }

    public function testAnUnknownEntryIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', '99999999-9999-4999-8999-999999999999'));
    }

    public function testAnEntryOfAnotherCollectionIsNotFoundThroughThisOne(): void
    {
        $this->writeFile('collections/pages.yaml', "title: Pages\n");
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('content-edit', 'pages', self::FIRST));
    }

    // Validation

    public function testValidationErrorsRerenderTheFormWithTheInputKept(): void
    {
        $before = glob($this->site . 'collections/blog/*');

        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint' => 'post',
            'fields'    => ['title' => '', 'tagline' => 'Kept tagline', 'summary' => "Kept\nsummary", 'featured' => '1', 'event_date' => '2026-03-03', 'body' => 'Kept *body*'],
        ]);

        $response->assertOK();
        $this->assertCount(1, $this->query($response, '//*[@data-error="title"]'));
        $this->assertSame('Kept tagline', $this->query($response, '//input[@name="fields[tagline]"]')->item(0)->getAttribute('value'));
        $this->assertSame('2026-03-03', $this->query($response, '//input[@name="fields[event_date]"]')->item(0)->getAttribute('value'));
        $this->assertTrue($this->query($response, '//input[@name="fields[featured]"][@type="checkbox"]')->item(0)->hasAttribute('checked'));
        $this->assertStringContainsString('Kept *body*', $this->query($response, '//textarea[@name="fields[body]"]')->item(0)->textContent);
        $this->assertStringContainsString("Kept\nsummary", $this->query($response, '//textarea[@name="fields[summary]"]')->item(0)->textContent);
        $this->assertSame($before, glob($this->site . 'collections/blog/*'));
        $this->assertSame(4, (new ContentEntryModel())->countAllResults());
    }

    public function testTheBlueprintsRulesApplyAndAnInvalidEditLeavesTheFileAlone(): void
    {
        $path   = $this->site . 'collections/blog/2026-01-10.first-post.md';
        $before = file_get_contents($path);

        $form   = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST));
        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::FIRST));

        $fields['fields']['title']      = str_repeat('x', 61);
        $fields['fields']['event_date'] = 'not a date';

        $response = $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::FIRST), $fields);

        $response->assertOK();
        $this->assertCount(1, $this->query($response, '//*[@data-error="title"]'));
        $this->assertCount(1, $this->query($response, '//*[@data-error="event_date"]'));
        $this->assertSame($before, file_get_contents($path));
    }

    public function testAnInvalidPublishDateIsRefused(): void
    {
        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Bad date'], 'published_at' => '2026-02-31T10:00']);

        $response->assertOK();
        $this->assertCount(1, $this->query($response, '//*[@data-error="published_at"]'));
        $this->assertNull((new ContentEntryModel())->where('slug', 'bad-date')->first());
    }

    public function testABlueprintThatIsNotOfTheCollectionIsRefused(): void
    {
        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['blueprint' => '../../etc', 'fields' => ['title' => 'Nope']]);

        $response->assertRedirectTo(site_url(route_to('content-list', 'blog')));
        $this->assertNull((new ContentEntryModel())->where('slug', 'nope')->first());
    }

    // Publishing

    public function testAUserWithoutPublishCannotPublishEvenByPostingTheField(): void
    {
        $editor = $this->editor();

        $response = $this->actingAs($editor)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Sneaky'], 'status' => 'published']);

        $response->assertStatus(403);
        $this->assertCount(1, $this->query($response, '//*[@data-error="status"]'));
        $this->assertNull((new ContentEntryModel())->where('slug', 'sneaky')->first());

        $path   = $this->site . 'collections/blog/2026-03-01.draft-post.md';
        $before = file_get_contents($path);
        $form   = $this->actingAs($editor)->get(route_to('content-edit', 'blog', self::DRAFT));
        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::DRAFT));

        $this->assertArrayNotHasKey('status', $fields, 'the status select is disabled');

        $this->actingAs($editor)->post(route_to('content-edit', 'blog', self::DRAFT), $fields + ['status' => 'published'])->assertStatus(403);

        $this->assertSame($before, file_get_contents($path));
        $this->assertSame('draft', (new ContentEntryModel())->find(self::DRAFT)['status']);
    }

    public function testAUserWithoutPublishCannotSetWhenAnEntryGoesLive(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Scheduled'], 'published_at' => '2030-01-01T00:00'])->assertStatus(403);

        $form   = $this->actingAs($editor)->get(route_to('content-edit', 'blog', self::DRAFT));
        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::DRAFT));

        $this->actingAs($editor)->post(route_to('content-edit', 'blog', self::DRAFT), $fields + ['published_at' => '2030-01-01T00:00'])->assertStatus(403);

        $this->assertNull((new ContentEntryModel())->where('slug', 'scheduled')->first());
    }

    public function testAUserWithoutPublishCanSaveDraftsAndEditPublishedEntries(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Mine']])->assertRedirect();
        $this->assertSame('draft', (new ContentEntryModel())->where('slug', 'mine')->first()['status']);

        $form                      = $this->actingAs($editor)->get(route_to('content-edit', 'blog', self::FIRST));
        $fields                    = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::FIRST));
        $fields['fields']['title'] = 'Edited by an editor';

        $this->actingAs($editor)->post(route_to('content-edit', 'blog', self::FIRST), $fields)->assertRedirect();

        $row = (new ContentEntryModel())->find(self::FIRST);
        $this->assertSame('Edited by an editor', $row['title']);
        $this->assertSame('published', $row['status']);
        $this->assertSame('2026-01-10 00:00:00', $row['published_at']);
        // The date still comes from the file name, because nobody changed it.
        $this->assertStringNotContainsString('published_at', (string) file_get_contents($this->site . $row['path']));
    }

    public function testAPublisherCanPublishADraftAndSetWhenItGoesLive(): void
    {
        $form   = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::DRAFT));
        $fields = $this->submittedForm($form->getBody(), url_to('content-edit', 'blog', self::DRAFT));

        $fields['status']       = 'published';
        $fields['published_at'] = '2026-07-04T12:00:30';

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::DRAFT), $fields)->assertRedirect();

        $row = (new ContentEntryModel())->find(self::DRAFT);
        $this->assertSame('published', $row['status']);
        $this->assertSame('2026-07-04 12:00:30', $row['published_at']);
    }

    // The markdown preview

    public function testThePreviewRendersMarkdownForHtmx(): void
    {
        $response = $this->actingAs($this->admin)->withHeaders(['HX-Request' => 'true'])->post(route_to('content-preview'), ['markdown' => "# Hi\n\n*there* <script>alert(1)</script>"]);

        $response->assertOK();
        $this->assertStringContainsString('<h1>Hi</h1>', $response->getBody());
        $this->assertStringContainsString('<em>there</em>', $response->getBody());
        $this->assertStringNotContainsString('<script>', $response->getBody());
    }

    public function testThePreviewRefusesARequestThatIsNotFromHtmx(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->post(route_to('content-preview'), ['markdown' => '# Hi']);
    }

    public function testTheMarkdownFieldHasAPreviewTabThatPostsToThePreviewRoute(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));

        $tab = $this->query($response, '//*[@data-markdown]//button[@data-tab="preview"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $tab);

        $this->assertSame(url_to('content-preview'), $tab->getAttribute('hx-post'));
    }

    // Overriding a field's input

    public function testAnAppCanOverrideAFieldsInputPartial(): void
    {
        $dir  = APPPATH . 'Views/fields';
        $file = $dir . '/text.php';
        $made = ! is_dir($dir) && mkdir($dir, 0777, true);
        file_put_contents($file, '<input type="text" name="<?= esc($name, "attr") ?>" data-overridden>');

        try {
            $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));
        } finally {
            unlink($file);
            if ($made) {
                rmdir($dir);
            }
        }

        $this->assertCount(1, $this->query($response, '//input[@name="fields[title]"][@data-overridden]'));
    }

    /**
     * What a browser would post for the form. It drops the line break right after <textarea>,
     * which the form puts there to keep a value that starts with its own, and the DOM here doesn't.
     *
     * @return array<string, mixed>
     */
    private function submittedForm(string $html, string $action): array
    {
        $fields = $this->formFields($html, $action);

        foreach (['summary', 'body'] as $handle) {
            if (isset($fields['fields'][$handle])) {
                $fields['fields'][$handle] = preg_replace('/^\n/', '', $fields['fields'][$handle], 1);
            }
        }

        return $fields;
    }

    private function editor(): User
    {
        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access', 'content.edit');

        return $user;
    }

    /**
     * @return DOMNodeList<DOMElement>
     */
    private function query(TestResponse $response, string $xpath, ?DOMNode $context = null): DOMNodeList
    {
        $key = spl_object_id($response);

        if (! isset($this->pages[$key])) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $response->getBody());
            libxml_clear_errors();

            $this->pages[$key] = [$response, new DOMXPath($dom)];
        }

        return $this->pages[$key][1]->query($xpath, $context);
    }
}
