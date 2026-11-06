<?php

namespace Tests\Content;

use Bonfire\Content\Indexer;
use Bonfire\Users\User;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Tests\Support\Content\ContentTestCase;

/**
 * The entry form of a blueprint that groups its fields in sections.
 *
 * @internal
 */
final class EntrySectionsTest extends ContentTestCase
{
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

    public function testEachSectionShowsItsTitleAndInstructionsAndItsOwnFields(): void
    {
        $this->useSections();

        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));
        $sections = $this->query($response, '//form[@data-entry-form]//fieldset[@data-section]');

        $this->assertCount(2, $sections);

        $first = $sections->item(0);
        $this->assertInstanceOf(DOMElement::class, $first);
        $this->assertSame('Main', trim($this->query($response, './legend', $first)->item(0)?->textContent ?? ''));
        $this->assertSame('What readers see first.', trim($this->query($response, './p', $first)->item(0)?->textContent ?? ''));
        $this->assertSame(['title', 'slug', 'tagline', 'summary'], $this->handlesIn($response, $first));

        $second = $sections->item(1);
        $this->assertInstanceOf(DOMElement::class, $second);
        $this->assertSame(['featured', 'event_date', 'body'], $this->handlesIn($response, $second));
        $this->assertCount(0, $this->query($response, './p', $second), 'a section without instructions shows none');
    }

    public function testFieldsAreLaidOutInTheSectionsColumnsAndStackBelowMd(): void
    {
        $this->useSections();

        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));

        // One column: a cell is the whole row at every size.
        $this->assertSame('col-12', $this->cellOf($response, 'tagline'));

        // Two columns: half the row from md up, a whole row below it.
        $this->assertSame('col-12 col-md-6', $this->cellOf($response, 'featured'));
        $this->assertSame('col-12 col-md-6', $this->cellOf($response, 'event_date'));
        $this->assertSame('2', $this->query($response, '//fieldset[@data-section][2]')->item(0)?->getAttribute('data-columns'));
    }

    public function testMarkdownAndTextareaFieldsSpanTheRowByDefault(): void
    {
        $this->useSections();

        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));

        $this->assertSame('col-12', $this->cellOf($response, 'body'), 'markdown, in a section of two columns');
        $this->assertSame('col-12', $this->cellOf($response, 'summary'));
    }

    public function testAFieldCanAskToSpanTheRow(): void
    {
        file_put_contents($this->blueprints . 'collections/blog/post.yaml', "sections:\n  - title: S\n    columns: 3\n    fields:\n      - handle: title\n        type: text\n      - handle: wide\n        type: text\n        width: full\n      - handle: narrow\n        type: text\n");

        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));

        $this->assertSame('col-12 col-md-4', $this->cellOf($response, 'title'));
        $this->assertSame('col-12', $this->cellOf($response, 'wide'));
        $this->assertSame('col-12 col-md-4', $this->cellOf($response, 'narrow'));
    }

    public function testABlueprintOfPlainFieldsShowsNoSections(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));

        $this->assertCount(0, $this->query($response, '//fieldset[@data-section]'));
        $this->assertCount(1, $this->query($response, '//*[@data-field="title"]'));
        $this->assertSame('col-12', $this->cellOf($response, 'tagline'));
    }

    public function testASectionedBlueprintSavesTheSameFileAsTheFlatOne(): void
    {
        $post = [
            'blueprint'    => 'post',
            'fields'       => ['title' => 'Same', 'slug' => 'same', 'tagline' => 'Hello', 'summary' => "Two\nlines", 'featured' => '1', 'event_date' => '2026-05-01', 'body' => "Some *Markdown*.\n"],
            'status'       => 'published',
            'published_at' => '2026-04-01T09:30',
        ];
        $path = $this->site . 'collections/blog/2026-04-01.same.md';

        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), $post)->assertRedirect();
        $this->assertFileExists($path);
        $flat = file_get_contents($path);

        unlink($path);
        (new Indexer($this->site))->reindex();

        $this->useSections();
        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), $post)->assertRedirect();
        $this->assertFileExists($path);

        $withoutId = static fn (string $raw): string => preg_replace('/^id: .*\n/m', '', $raw, 1);

        $this->assertSame($withoutId($flat), $withoutId(file_get_contents($path)));
    }

    public function testValidationErrorsShowInTheFieldsOfASection(): void
    {
        $this->useSections();

        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['fields' => ['title' => '']]);

        $this->assertCount(1, $this->query($response, '//fieldset[@data-section][1]//*[@data-error="title"]'));
    }

    public function testASectionedGlobalSetGroupsItsFieldsToo(): void
    {
        service('routes')->loadRoutes();
        mkdir($this->site . 'globals');
        mkdir($this->blueprints . 'globals');
        $this->writeFile('globals/site.yaml', "name: Bonfire\n");
        file_put_contents($this->blueprints . 'globals/site.yaml', "sections:\n  - title: Identity\n    fields:\n      - handle: name\n        type: text\n");

        $response = $this->actingAs($this->admin)->get(route_to('content-global', 'site'));

        $this->assertCount(1, $this->query($response, '//fieldset[@data-section]//*[@data-field="name"]'));
    }

    /**
     * The handles of the fields in an element, in order.
     *
     * @return list<string>
     */
    private function handlesIn(TestResponse $response, DOMElement $context): array
    {
        $handles = [];

        foreach ($this->query($response, './/*[@data-field]', $context) as $field) {
            $handles[] = $field->getAttribute('data-field');
        }

        return $handles;
    }

    /**
     * The classes of the cell that holds the field.
     */
    private function cellOf(TestResponse $response, string $handle): string
    {
        return $this->query($response, '//*[@data-field="' . $handle . '"]/parent::div')->item(0)?->getAttribute('class') ?? '';
    }

    /**
     * @return DOMNodeList<DOMElement>
     */
    private function query(TestResponse $response, string $xpath, ?DOMElement $context = null): DOMNodeList
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
