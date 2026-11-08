<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\Entry;
use Bonfire\Content\Fields\ListField;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\SubmitsForms;

/**
 * The admin editor of a list field: its rows, the fragment that adds one, and saving what the rows post.
 *
 * @internal
 */
final class ListEditorTest extends ContentTestCase
{
    use SubmitsForms;

    private const ENTRY = '11111111-1111-4111-8111-111111111111';
    private const FILE  = 'collections/blog/2026-01-10.first-post.md';
    private const ROWS  = "    fields:\n      - {handle: title, type: text, validate: required}\n      - {handle: type, type: text}\n";

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);
        config('Encryption')->key = 'a-key-for-the-tests';
        $this->resetAdminServices();

        $this->writeBlueprint();
        $this->writeRows("features:\n  - {title: One, type: a}\n  - {title: Two}\n  - {title: Three}\n");

        service('routes')->loadRoutes();

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        $this->resetAdminServices();

        parent::tearDown();
    }

    // The rows

    public function testEachRowIsACardWithItsOwnKeyAndAllItsButtons(): void
    {
        $page  = $this->page();
        $cards = $page->query('//*[@data-list-rows]/*[@data-row]');

        $this->assertSame(3, $cards->length);

        $keys = array_map(static fn (DOMElement $card): string => $card->getAttribute('data-block-key'), iterator_to_array($cards));
        $this->assertCount(3, array_unique($keys));

        foreach (['data-handle', 'data-block-up', 'data-block-down', 'data-block-duplicate', 'data-block-delete'] as $part) {
            $this->assertSame(3, $page->query("//*[@data-row]//*[@{$part}]")->length, $part);
        }
    }

    public function testASubFieldCanBeNamedTypeWithoutARowGettingATypeInput(): void
    {
        $page = $this->page();
        $key  = $this->firstKey($page);

        $inputs = $page->query("//input[@name='fields[features][{$key}][type]']");

        $this->assertSame(1, $inputs->length);
        $this->assertSame('text', $inputs->item(0)->getAttribute('type'));
        $this->assertSame('a', $inputs->item(0)->getAttribute('value'));
    }

    public function testRowsStartClosed(): void
    {
        foreach ($this->page()->query('//*[@data-row]') as $card) {
            $this->assertSame('{ open: false }', $card->getAttribute('x-data'));
        }
    }

    public function testThePageLoadsSortableForATopLevelList(): void
    {
        $this->assertStringContainsString('Sortable.min.js', $this->editBody());
    }

    // Adding

    public function testTheAddButtonAsksTheServerForARow(): void
    {
        $button = $this->page()->query('//button[@data-add-row]')->item(0);

        $this->assertSame(url_to('content-list-row'), $button->getAttribute('hx-get'));
        $this->assertSame('#field-features-list', $button->getAttribute('hx-target'));
        $this->assertSame('beforeend', $button->getAttribute('hx-swap'));
        $this->assertStringContainsString(esc($this->token(), 'js'), (string) $button->getAttribute('hx-vals'));
    }

    public function testTheFragmentIsAnOpenRowWithTheInputsOfTheSubFields(): void
    {
        $response = $this->fragment();
        $response->assertOK();

        $row  = $this->dom($response->getBody());
        $card = $row->query('//*[@data-row]')->item(0);
        $key  = $card->getAttribute('data-block-key');

        $this->assertMatchesRegularExpression('/^b_[0-9a-f]{6}$/', $key);
        $this->assertSame('{ open: true }', $card->getAttribute('x-data'));
        $this->assertSame(1, $row->query("//input[@name='fields[features][{$key}][title]']")->length);
        $this->assertSame(1, $row->query("//input[@id='field-features-{$key}-title']")->length);
    }

    public function testAFragmentForAListInsideABlockIsNamedWithinTheBlock(): void
    {
        $row = $this->dom($this->fragment(['name' => 'fields[sections][b_aaaaaa][features]', 'id' => 'field-sections-b_aaaaaa-features'])->getBody());
        $key = $row->query('//*[@data-row]')->item(0)->getAttribute('data-block-key');

        $this->assertSame(1, $row->query("//input[@name='fields[sections][b_aaaaaa][features][{$key}][title]']")->length);
    }

    #[DoesNotPerformAssertions]
    public function testAFragmentNeedsAValidToken(): void
    {
        $token = $this->token();

        foreach (['', 'nope', $token . 'x', 'e30.' . explode('.', $token)[1]] as $forged) {
            try {
                $this->fragment(['token' => $forged]);
                $this->fail('A forged token was accepted: ' . $forged);
            } catch (PageNotFoundException) {
            }
        }
    }

    #[DoesNotPerformAssertions]
    public function testAFragmentRefusesANameOrIdThatIsNotAnInputs(): void
    {
        foreach ([['name' => 'x"><script>'], ['id' => 'x y']] as $bad) {
            try {
                $this->fragment($bad);
                $this->fail('A bad name or id was accepted.');
            } catch (PageNotFoundException) {
            }
        }
    }

    public function testATokenCannotComeFromAnotherSigningPurpose(): void
    {
        $payload = rtrim(strtr(base64_encode('{"handle":"x","fields":[{"handle":"a","type":"text"}]}'), '+/', '-_'), '=');

        $this->expectException(PageNotFoundException::class);

        $this->fragment(['token' => $payload . '.' . hash_hmac('sha256', 'fields-options|' . $payload, 'a-key-for-the-tests')]);
    }

    public function testAFragmentNeedsPermissionToEdit(): void
    {
        $viewer = $this->createUser();
        $viewer->addPermission('admin.access', 'content.access');

        $this->actingAs($viewer)->get(route_to('content-list-row'), $this->fragmentQuery())->assertRedirectTo(site_url(ADMIN_AREA));
    }

    public function testAddIsDisabledAtMaxItemsAndTheServerRefusesIt(): void
    {
        $this->writeBlueprint("    max_items: 3\n");

        $this->assertTrue($this->page()->query('//button[@data-add-row]')->item(0)->hasAttribute('disabled'));

        $refused = $this->fragment(['count' => 3]);
        $this->assertSame(422, $refused->response()->getStatusCode());
        $this->assertSame('', trim($refused->getBody()));

        $this->fragment(['count' => 2])->assertOK();
    }

    public function testWithoutAnEncryptionKeyTheRowsShowButNoneCanBeAdded(): void
    {
        config('Encryption')->key = '';

        $page = $this->page();

        $this->assertSame(3, $page->query('//*[@data-row]')->length);
        $this->assertSame(0, $page->query('//button[@data-add-row]')->length);
        $this->assertSame(1, $page->query('//*[@data-list-no-key]')->length);
    }

    // Saving

    public function testMovingDuplicatingAddingAndDeletingRowsWritesThemInTheShownOrder(): void
    {
        $fields              = $this->formFields($this->editBody(), url_to('content-edit', 'blog', self::ENTRY));
        [$one, $two, $three] = array_values($fields['fields']['features']);

        $this->assertSame('Three', $three['title']);

        $fields['fields']['features'] = [
            'b_000001' => $two,
            'b_000002' => $one,
            'b_000003' => $one,
            'b_000004' => ['title' => 'Added'],
        ];

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::ENTRY), $fields)->assertRedirect();

        $this->assertSame(
            [['title' => 'Two'], ['title' => 'One', 'type' => 'a'], ['title' => 'One', 'type' => 'a'], ['title' => 'Added']],
            Entry::fromRow((new ContentEntryModel())->find(self::ENTRY))->data['features'],
        );
    }

    public function testARowWhoseInputsPostNothingWhenEmptyIsKept(): void
    {
        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n  - handle: slug\n    type: slug\n  - handle: features\n    type: list\n    min_items: 1\n    fields:\n      - {handle: picks, type: select, multiple: true, options: [a, b]}\n",
        );
        $this->writeRows("features:\n  - {picks: []}\n");

        $body = $this->editBody();

        $this->assertSame(1, $this->dom($body)->query("//*[@data-row]//input[@type='hidden'][contains(@name, '[_row]')]")->length);

        // A browser posts nothing for a multiple select with nothing chosen, which the form helper doesn't copy.
        $fields = $this->formFields($body, url_to('content-edit', 'blog', self::ENTRY));
        unset($fields['fields']['features'][array_key_first($fields['fields']['features'])]['picks']);

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::ENTRY), $fields)->assertRedirect();

        $this->assertSame([[]], Entry::fromRow((new ContentEntryModel())->find(self::ENTRY))->data['features']);
    }

    public function testDeletingEveryRowEmptiesTheList(): void
    {
        $fields = $this->formFields($this->editBody(), url_to('content-edit', 'blog', self::ENTRY));
        unset($fields['fields']['features']);

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::ENTRY), $fields)->assertRedirect();

        $this->assertSame([], Entry::fromRow((new ContentEntryModel())->find(self::ENTRY))->data['features']);
    }

    public function testSavingMoreRowsThanMaxItemsIsRefusedAndWritesNothing(): void
    {
        $this->writeBlueprint("    max_items: 3\n");

        $before = file_get_contents($this->site . self::FILE);
        $fields = $this->formFields($this->editBody(), url_to('content-edit', 'blog', self::ENTRY));

        $fields['fields']['features']['b_000009'] = ['title' => 'Fourth'];

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::ENTRY), $fields)->assertOK();

        $this->assertSame($before, file_get_contents($this->site . self::FILE));
    }

    public function testAnErrorOpensItsRowAndIsLabelledByThePosition(): void
    {
        $fields = $this->formFields($this->editBody(), url_to('content-edit', 'blog', self::ENTRY));
        $keys   = array_keys($fields['fields']['features']);

        $fields['fields']['features'][$keys[1]]['title'] = '';

        $response = $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', self::ENTRY), $fields);
        $response->assertOK();

        $page = $this->dom($response->getBody());

        $this->assertSame(['{ open: false }', '{ open: true }', '{ open: false }'], array_map(
            static fn (DOMElement $card): string => $card->getAttribute('x-data'),
            iterator_to_array($page->query('//*[@data-list-rows]/*[@data-row]')),
        ));
        $this->assertSame(1, $page->query('(//*[@data-row])[2]//*[@data-block-errors]/*[@data-error="2.title"]')->length);
    }

    // Inside a block

    public function testAListInsideABlockRendersItsRowsNamedWithinTheBlock(): void
    {
        mkdir($this->blueprints . 'blocks');
        file_put_contents($this->blueprints . 'blocks/pricing.yaml', "title: Pricing\nfields:\n  - handle: tiers\n    type: list\n" . self::ROWS);
        file_put_contents($this->blueprints . 'collections/blog/post.yaml', "title: Post\nfields:\n  - handle: title\n    type: text\n  - handle: slug\n    type: slug\n  - handle: sections\n    type: blocks\n");
        $this->writeRows("sections:\n  - type: pricing\n    tiers:\n      - {title: Basic}\n");

        $page  = $this->page();
        $block = $page->query('//*[@data-block="pricing"]')->item(0)->getAttribute('data-block-key');
        $row   = $page->query('//*[@data-row]')->item(0)->getAttribute('data-block-key');

        $this->assertSame(1, $page->query("//input[@name='fields[sections][{$block}][tiers][{$row}][title]'][@value='Basic']")->length);
    }

    // Helpers

    private function resetAdminServices(): void
    {
        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }
    }

    private function writeBlueprint(string $options = ''): void
    {
        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n  - handle: features\n    type: list\n" . self::ROWS . $options,
        );
    }

    private function writeRows(string $frontMatter): void
    {
        $this->writeFile(self::FILE, "---\nid: " . self::ENTRY . "\ntitle: First post\n{$frontMatter}---\nThe first *post*.\n");
        (new Indexer($this->site))->reindex();
    }

    private function token(): string
    {
        return ListField::token(Blueprint::load($this->blueprints, 'blog', 'post')->field('features'));
    }

    /**
     * @param array<string, int|string> $query what to change in the request
     *
     * @return array<string, int|string>
     */
    private function fragmentQuery(array $query = []): array
    {
        return $query + ['token' => $this->token(), 'name' => 'fields[features]', 'id' => 'field-features', 'count' => 0];
    }

    /**
     * @param array<string, int|string> $query
     */
    private function fragment(array $query = []): TestResponse
    {
        return $this->actingAs($this->admin)->get(route_to('content-list-row'), $this->fragmentQuery($query));
    }

    private function editBody(): string
    {
        return $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::ENTRY))->getBody();
    }

    private function page(): DOMXPath
    {
        return $this->dom($this->editBody());
    }

    private function firstKey(DOMXPath $page): string
    {
        return $page->query('//*[@data-row]')->item(0)->getAttribute('data-block-key');
    }

    private function dom(string $html): DOMXPath
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);

        libxml_clear_errors();

        return new DOMXPath($dom);
    }
}
