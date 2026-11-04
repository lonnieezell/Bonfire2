<?php

namespace Tests\Content;

use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentTermModel;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Services;
use Symfony\Component\Yaml\Yaml;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\SubmitsForms;

/**
 * @internal
 */
final class TermAdminTest extends ContentTestCase
{
    use SubmitsForms;

    private const FIRST = '11111111-1111-4111-8111-111111111111';
    private const PHP   = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        // FrontEndRoutingTest leaves the routes reset and not loaded, and route_to() before a request then finds none of them.
        service('routes')->loadRoutes();

        $this->useTags();
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

    // Tagging an entry

    public function testTheEntryFormOffersTheTermsAndChecksTheOnesTheEntryHas(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST));

        $response->assertOK();
        $fields = $this->formFields($response->getBody(), url_to('content-edit', 'blog', self::FIRST));

        // In the order of the terms' titles.
        $this->assertSame(['laravel', 'php'], $fields['fields']['tags']);
        $response->assertSee('PHP');
        $response->assertSee('Laravel');
    }

    public function testTaggingAnEntryWritesTheSlugsToItsFrontMatterAndUpdatesThePivot(): void
    {
        $url    = route_to('content-edit', 'blog', self::FIRST);
        $form   = $this->actingAs($this->admin)->get($url);
        $fields = $this->formFields($form->getBody(), url_to('content-edit', 'blog', self::FIRST));

        $fields['fields']['tags'] = ['laravel'];

        $this->actingAs($this->admin)->post($url, $fields)->assertRedirect();

        $this->assertSame(['laravel'], $this->frontMatterTags('collections/blog/2026-01-10.first-post.md'));
        $this->assertSame(['bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'], $this->termIdsOf(self::FIRST));

        // The index agrees with a rebuild from the files.
        (new Indexer($this->site))->reindex();
        $this->assertSame(['bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'], $this->termIdsOf(self::FIRST));
    }

    public function testUncheckingEveryTermEmptiesTheList(): void
    {
        $url    = route_to('content-edit', 'blog', self::FIRST);
        $fields = $this->formFields($this->actingAs($this->admin)->get($url)->getBody(), url_to('content-edit', 'blog', self::FIRST));

        unset($fields['fields']['tags']);

        $this->actingAs($this->admin)->post($url, $fields)->assertRedirect();

        $this->assertSame([], $this->frontMatterTags('collections/blog/2026-01-10.first-post.md'));
        $this->assertSame([], $this->termIdsOf(self::FIRST));
    }

    public function testANewEntryCanBeTaggedAndWithoutTagsGetsNoKey(): void
    {
        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Tagged', 'tags' => ['php']]])->assertRedirect();
        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => 'Untagged']])->assertRedirect();

        $tagged = date('Y-m-d') . '.tagged.md';
        $plain  = date('Y-m-d') . '.untagged.md';

        $this->assertSame(['php'], $this->frontMatterTags("collections/blog/{$tagged}"));
        $this->assertStringNotContainsString('tags', (string) file_get_contents($this->site . "collections/blog/{$plain}"));

        $row = db_connect()->table('content_entries')->where('slug', 'tagged')->get()->getRowArray();
        $this->assertSame([self::PHP], $this->termIdsOf($row['id']));
    }

    public function testATagNoTermHasStaysInTheFileWhenTheEntryIsSaved(): void
    {
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: " . self::FIRST . "\ntitle: First post\ntags: [php, ghost]\n---\nBody.\n");
        (new Indexer($this->site))->reindex();

        $url  = route_to('content-edit', 'blog', self::FIRST);
        $form = $this->actingAs($this->admin)->get($url);
        $form->assertSee('ghost (no such term)');

        $this->actingAs($this->admin)->post($url, $this->formFields($form->getBody(), url_to('content-edit', 'blog', self::FIRST)))->assertRedirect();

        $this->assertSame(['php', 'ghost'], $this->frontMatterTags('collections/blog/2026-01-10.first-post.md'));
    }

    // The terms

    public function testTheTermListShowsEachTermWithItsEntryCount(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-terms', 'tags'));

        $response->assertOK();
        $body = $response->getBody();
        $this->assertStringContainsString('data-term="php"', $body);
        $this->assertStringContainsString('data-term="laravel"', $body);
        $this->assertStringContainsString(route_to('content-term-edit', 'tags', 'php'), $body);
    }

    public function testCreatingATermWritesATermFileAndIndexesIt(): void
    {
        $response = $this->actingAs($this->admin)->post(route_to('content-term-new', 'tags'), ['fields' => ['title' => 'Déjà Vu!']]);

        $response->assertRedirectTo(site_url(route_to('content-term-edit', 'tags', 'déjà-vu')));

        $file = $this->site . 'taxonomies/tags/déjà-vu.yaml';
        $this->assertFileExists($file);

        $data = Yaml::parseFile($file);
        $this->assertSame('Déjà Vu!', $data['title']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/', $data['id']);

        $row = (new ContentTermModel())->find($data['id']);
        $this->assertSame(['tags', 'déjà-vu', 'Déjà Vu!', 'taxonomies/tags/déjà-vu.yaml'], [$row['taxonomy'], $row['slug'], $row['title'], $row['path']]);

        // The index agrees with a rebuild from the files.
        (new Indexer($this->site))->reindex();
        $this->assertSame($row['hash'], (new ContentTermModel())->find($data['id'])['hash']);
    }

    public function testANewTermWithATypedSlugNamesTheFile(): void
    {
        $this->actingAs($this->admin)->post(route_to('content-term-new', 'tags'), ['fields' => ['title' => 'Whatever', 'slug' => 'My Own Slug']])->assertRedirect();

        $this->assertFileExists($this->site . 'taxonomies/tags/my-own-slug.yaml');
    }

    public function testATermRefusesAMissingTitleADuplicateSlugAndTheReservedNew(): void
    {
        $count = count(glob($this->site . 'taxonomies/tags/*.yaml'));

        $empty = $this->actingAs($this->admin)->post(route_to('content-term-new', 'tags'), ['fields' => ['title' => '']]);
        $empty->assertOK();
        $this->assertStringContainsString('data-error="title"', $empty->getBody());

        $dupe = $this->actingAs($this->admin)->post(route_to('content-term-new', 'tags'), ['fields' => ['title' => 'Again', 'slug' => 'php']]);
        $dupe->assertOK();
        $this->assertStringContainsString('data-notice', $dupe->getBody());

        $reserved = $this->actingAs($this->admin)->post(route_to('content-term-new', 'tags'), ['fields' => ['title' => 'New']]);
        $reserved->assertOK();
        $this->assertStringContainsString('data-notice', $reserved->getBody());

        $this->assertCount($count, glob($this->site . 'taxonomies/tags/*.yaml'));
        $this->assertSame('id: ' . self::PHP . "\ntitle: PHP\ncolor: blue\n", file_get_contents($this->site . 'taxonomies/tags/php.yaml'));
    }

    public function testEditingATermRewritesItsFileAndKeepsItsIdAndOtherKeys(): void
    {
        $url    = route_to('content-term-edit', 'tags', 'php');
        $form   = $this->actingAs($this->admin)->get($url);
        $fields = $this->formFields($form->getBody(), url_to('content-term-edit', 'tags', 'php'));

        $this->assertSame('PHP', $fields['fields']['title']);

        $fields['fields']['title'] = 'PHP 8';

        $this->actingAs($this->admin)->post($url, $fields)->assertRedirect();

        $this->assertSame(['id' => self::PHP, 'title' => 'PHP 8', 'color' => 'blue'], Yaml::parseFile($this->site . 'taxonomies/tags/php.yaml'));
        $this->assertSame('PHP 8', (new ContentTermModel())->find(self::PHP)['title']);
        $this->assertFileExists($this->site . 'taxonomies/tags/php.yaml');
    }

    public function testATermBlueprintAddsFieldsToTheTermFile(): void
    {
        mkdir($this->blueprints . 'taxonomies');
        file_put_contents($this->blueprints . 'taxonomies/tags.yaml', "title: Tag\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n  - handle: color\n    type: text\n");

        $this->actingAs($this->admin)->post(route_to('content-term-new', 'tags'), ['fields' => ['title' => 'Vue', 'color' => 'green']])->assertRedirect();

        $this->assertSame('green', Yaml::parseFile($this->site . 'taxonomies/tags/vue.yaml')['color']);

        $this->actingAs($this->admin)->get(route_to('content-term-edit', 'tags', 'php'))->assertSee('fields[color]');
    }

    public function testAnUnknownTaxonomyOrTermIs404(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->actingAs($this->admin)->get(route_to('content-terms', 'nope'));
    }

    public function testAnUnknownTermIs404(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->actingAs($this->admin)->get(route_to('content-term-edit', 'tags', 'ghost'));
    }

    // Permissions

    public function testTermsNeedContentStructure(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access', 'content.access', 'content.edit', 'content.publish');

        $before = file_get_contents($this->site . 'taxonomies/tags/php.yaml');
        $paths  = [
            ['get', route_to('content-terms', 'tags')],
            ['get', route_to('content-term-new', 'tags')],
            ['post', route_to('content-term-new', 'tags')],
            ['get', route_to('content-term-edit', 'tags', 'php')],
            ['post', route_to('content-term-edit', 'tags', 'php')],
        ];

        foreach ($paths as [$method, $path]) {
            $response = $this->actingAs($user)->{$method}($path, ['fields' => ['title' => 'Hacked']]);

            $response->assertRedirectTo(site_url(ADMIN_AREA));
            $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
        }

        $this->assertSame($before, file_get_contents($this->site . 'taxonomies/tags/php.yaml'));
        $this->assertFileDoesNotExist($this->site . 'taxonomies/tags/hacked.yaml');
    }

    public function testTheMenuShowsTaxonomiesOnlyToThoseWithContentStructure(): void
    {
        $this->actingAs($this->admin)->get(ADMIN_AREA)->assertSee(route_to('content-terms', 'tags'));

        Services::resetSingle('auth');
        Services::resetSingle('menus');

        $editor = $this->createUser();
        $editor->addPermission('admin.access', 'content.access');

        $dashboard = $this->actingAs($editor)->get(ADMIN_AREA);

        $dashboard->assertSee(route_to('content-list', 'blog'));
        $this->assertStringNotContainsString(route_to('content-terms', 'tags'), $dashboard->getBody());
    }

    /**
     * @return list<string>
     */
    private function frontMatterTags(string $path): array
    {
        preg_match('/\A---\R(.*?)^---/ms', (string) file_get_contents($this->site . $path), $matches);

        return Yaml::parse($matches[1])['tags'];
    }

    /**
     * @return list<string>
     */
    private function termIdsOf(string $entryId): array
    {
        $ids = array_column(db_connect()->table('content_entry_terms')->where('entry_id', $entryId)->get()->getResultArray(), 'term_id');
        sort($ids);

        return $ids;
    }
}
