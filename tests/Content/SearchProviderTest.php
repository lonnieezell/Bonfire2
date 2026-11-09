<?php

namespace Tests\Content;

use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Content\SearchProvider;
use Bonfire\Users\User;
use Config\Services;
use Tests\Support\Content\ContentTestCase;

/**
 * @internal
 */
final class SearchProviderTest extends ContentTestCase
{
    private const FIRST = '11111111-1111-4111-8111-111111111111';
    private const DRAFT = '33333333-3333-4333-8333-333333333333';

    private SearchProvider $provider;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        (new Indexer($this->site))->reindex();

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
        auth()->login($this->admin);

        $this->provider = new SearchProvider();
    }

    protected function tearDown(): void
    {
        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    /**
     * Signs the user in, in place of whoever is, for a direct call of the provider or for a request.
     */
    private function signInAs(User $user): static
    {
        auth()->logout();

        return $this->actingAs($user);
    }

    private function userWith(string ...$permissions): User
    {
        $user = $this->createUser();
        $user->addPermission(...$permissions);

        return $user;
    }

    /**
     * The HTML of the global search for a term that only the draft post's body has.
     */
    private function searchPage(User $user): string
    {
        $result = $this->signInAs($user)->post(ADMIN_AREA . '/search', ['search_term' => 'Not ready']);
        $result->assertOK();

        return (string) $result->response()->getBody();
    }

    public function testTheGlobalSearchShowsTheEntryWithItsCollectionAndStatus(): void
    {
        $body = $this->searchPage($this->admin);

        $this->assertStringContainsString('href="' . url_to('content-edit', 'blog', self::DRAFT) . '"', $body);
        $this->assertStringContainsString('data-collection="blog"', $body);
        $this->assertStringContainsString('data-status="draft"', $body);
        $this->assertStringContainsString('data-entry="' . self::DRAFT . '"', $body);
    }

    public function testAWordInTheBodyAloneFindsTheEntry(): void
    {
        $results = $this->provider->search('Not ready');

        $this->assertSame([self::DRAFT], array_column($results, 'id'));
    }

    public function testAWordInTheTitleFindsTheEntry(): void
    {
        // The title is "First post", and the body "The first *post*."
        $results = $this->provider->search('First post');

        $this->assertSame([self::FIRST], array_column($results, 'id'));
    }

    public function testResultsAreInTitleOrder(): void
    {
        $this->assertSame(
            ['Draft post', 'First post', 'Future post', 'Second post'],
            array_column($this->provider->search('post'), 'title'),
        );
    }

    public function testMarkdownSyntaxInTheBodyIsNotFound(): void
    {
        $this->assertSame([], $this->provider->search('*post*'));
        $this->assertCount(1, $this->provider->search('The first post'));
    }

    public function testAPageIsFoundByTextOnlyInOneOfItsBlocks(): void
    {
        $this->writeFile('collections/blog/2026-04-01.built.md', "---\nid: 44444444-4444-4444-8444-444444444444\ntitle: Built\nsections:\n  - type: hero\n    heading: A wandering capybara\n---\nPlain.\n");
        (new Indexer($this->site))->reindex();

        $this->assertSame(['44444444-4444-4444-8444-444444444444'], array_column($this->provider->search('capybara'), 'id'));
    }

    public function testFragmentEntriesAreFound(): void
    {
        mkdir($this->site . 'collections/promos');
        $this->writeFile('collections/promos.yaml', "title: Promos\nfragments: true\n");
        $this->writeFile('collections/promos/spring-sale.md', "---\nid: 55555555-5555-4555-8555-555555555555\ntitle: Spring sale\n---\nHalf price capybaras.\n");
        (new Indexer($this->site))->reindex();

        $this->assertSame(['55555555-5555-4555-8555-555555555555'], array_column($this->provider->search('capybaras'), 'id'));
    }

    public function testLikeWildcardsInTheTermAreTakenLiterally(): void
    {
        $this->assertSame([], $this->provider->search('%'));
        $this->assertSame([], $this->provider->search('_'));
    }

    public function testAQuoteInTheTermIsMatchedAsWritten(): void
    {
        $this->assertSame([], $this->provider->search("zebra's"));

        (new ContentEntryModel())->update(self::DRAFT, ['search_text' => "The zebra's stripes"]);

        $this->assertSame([self::DRAFT], array_column($this->provider->search("zebra's"), 'id'));
    }

    public function testTrashedEntriesAreNotReturned(): void
    {
        // "The first" is in only the body, and "First post" is the title.
        $this->assertCount(1, $this->provider->search('The first'));
        $this->assertCount(1, $this->provider->search('First post'));

        (new ContentEntryModel())->delete(self::FIRST);

        $this->assertSame([], $this->provider->search('The first'));
        $this->assertSame([], $this->provider->search('First post'));
    }

    public function testTheLimitCapsTheResults(): void
    {
        $this->assertCount(2, $this->provider->search('post', 2));
    }

    public function testWithoutContentAccessNothingIsReturned(): void
    {
        $this->signInAs($this->userWith('admin.access'));

        $this->assertSame([], $this->provider->search('Not ready'));
    }

    public function testSeeAllGoesToACollectionListThatExists(): void
    {
        $url = $this->provider->resourceUrl();

        $this->assertSame('/' . ADMIN_AREA . '/content/blog', $url);
        $this->signInAs($this->admin)->get($url)->assertOK();
    }

    public function testSeeAllDoesNotSendThoseWithoutContentAccessToAPageThatRefusesThem(): void
    {
        $this->signInAs($this->userWith('admin.access'));

        $this->assertSame(ADMIN_AREA, $this->provider->resourceUrl());
    }

    public function testSeeAllFallsBackToTheDashboardWithoutCollections(): void
    {
        unlink($this->site . 'collections/blog.yaml');

        $this->assertSame(ADMIN_AREA, $this->provider->resourceUrl());
    }

    public function testTheGlobalSearchHidesEntriesFromThoseWithoutContentAccess(): void
    {
        $this->assertStringNotContainsString('data-entry="' . self::DRAFT . '"', $this->searchPage($this->userWith('admin.access')));
    }

    public function testTheResultsLinkToTheEditFormOnlyForThoseWhoCanEdit(): void
    {
        $body = $this->searchPage($this->userWith('admin.access', 'content.access'));

        $this->assertStringContainsString('data-entry="' . self::DRAFT . '"', $body);
        $this->assertStringNotContainsString(url_to('content-edit', 'blog', self::DRAFT), $body);
    }

    public function testAnEntryOfACollectionThatDoesNotLoadHasNoLinkToAFormThatWouldNotOpen(): void
    {
        unlink($this->site . 'collections/blog.yaml');

        $body = $this->searchPage($this->admin);

        $this->assertStringContainsString('data-entry="' . self::DRAFT . '"', $body);
        $this->assertStringNotContainsString(url_to('content-edit', 'blog', self::DRAFT), $body);
    }
}
