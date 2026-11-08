<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\Entry;
use Bonfire\Content\EntryForm;
use Bonfire\Content\Fields\BlocksField;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Symfony\Component\Yaml\Yaml;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\Content\ShoutField;
use Tests\Support\SubmitsForms;

/**
 * The admin editor of a blocks field: its cards, the fragment that adds one, and saving what the cards post.
 *
 * @internal
 */
final class BlocksEditorTest extends ContentTestCase
{
    use SubmitsForms;

    private const FIRST = '11111111-1111-4111-8111-111111111111';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);
        config('Encryption')->key = 'a-key-for-the-tests';

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        mkdir($this->blueprints . 'blocks');
        $this->writeBlock('hero', "title: Hero\nicon: star\nfields:\n  - handle: heading\n    type: text\n    validate: required|max_length[20]\n  - handle: subheading\n    type: text\n");
        $this->writeBlock('cta', "title: Call to action\nfields:\n  - handle: label\n    type: text\n  - handle: new_tab\n    type: toggle\n  - handle: count\n    type: integer\n    min: 1\n");
        $this->writeBlock('article', "title: Article\nfields:\n  - handle: heading\n    type: text\n  - handle: body\n    type: markdown\n");

        $this->useBlocksField();
        $this->writeSections("sections:\n  - type: hero\n    heading: Welcome\n  - type: cta\n    label: Join\n    new_tab: true\n    count: 3\n  - type: hero\n    heading: Goodbye\n");

        service('routes')->loadRoutes();

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

    // The cards

    public function testEachBlockIsACardInTheOrderOfTheFile(): void
    {
        $page = $this->edit();

        $this->assertSame(['hero', 'cta', 'hero'], $this->attributes($page, '//*[@data-blocks-list]/*[@data-block]', 'data-block'));
        $this->assertSame(['Hero', 'Call to action', 'Hero'], $this->texts($page, '//*[@data-block]//*[@data-block-title]'));
        $this->assertSame('Welcome', $this->texts($page, '//*[@data-block]//*[@data-summary]')[0]);

        foreach (['data-handle', 'data-block-up', 'data-block-down', 'data-block-duplicate', 'data-block-delete'] as $part) {
            $this->assertCount(3, $this->query($page, "//*[@data-block]//*[@{$part}]"), $part);
        }
    }

    public function testTheInputsOfABlockAreNamedWithARandomKeyThatIsDifferentForEachBlock(): void
    {
        $keys = $this->attributes($this->edit(), '//*[@data-block]', 'data-block-key');

        $this->assertCount(3, array_unique($keys));

        foreach ($keys as $key) {
            $this->assertMatchesRegularExpression('/^b_[0-9a-f]{6}$/', $key);
        }

        $page = $this->edit();
        $this->assertNotSame($keys, $this->attributes($page, '//*[@data-block]', 'data-block-key'));
        $this->assertCount(1, $this->query($page, '//input[@type="hidden"][@value="cta"][contains(@name, "fields[sections][b_")][contains(@name, "][type]")]'));
    }

    public function testACardOpensOnlyWhenItHasAnError(): void
    {
        $page = $this->edit();

        foreach ($this->attributes($page, '//*[@data-block]', 'x-data') as $state) {
            $this->assertSame('{ open: false }', $state);
        }
    }

    public function testMarkdownFieldsInABlockKeepThePlainPreviewTab(): void
    {
        $this->writeSections("sections:\n  - type: article\n    heading: Hi\n    body: '**bold**'\n");

        $page = $this->edit();
        $key  = $this->attributes($page, '//*[@data-block]', 'data-block-key')[0];

        $this->assertCount(1, $this->query($page, "//textarea[@id='field-sections-{$key}-body']"));

        $tab = $this->query($page, '//*[@data-block]//button[@data-tab="preview"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $tab);
        $this->assertSame(url_to('content-preview'), $tab->getAttribute('hx-post'));
        $this->assertStringContainsString(esc("field-sections-{$key}-body", 'js'), $tab->getAttribute('hx-vals'));
    }

    // Adding

    public function testAddingABlockFetchesItsFormFragmentFromTheServer(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-block', 'sections', 'hero'));
        $response->assertOK();

        $fragment = $this->html($response);
        $key      = $this->attributes($fragment, '//*[@data-block="hero"]', 'data-block-key')[0];

        $this->assertCount(1, $this->query($fragment, "//input[@type='hidden'][@name='fields[sections][{$key}][type]'][@value='hero']"));
        $this->assertCount(1, $this->query($fragment, "//input[@type='text'][@name='fields[sections][{$key}][heading]']"));
        $this->assertCount(1, $this->query($fragment, "//input[@type='text'][@name='fields[sections][{$key}][subheading]']"));
        $this->assertSame('{ open: true }', $this->attributes($fragment, '//*[@data-block]', 'x-data')[0]);

        $other = $this->html($this->actingAs($this->admin)->get(route_to('content-block', 'sections', 'hero')));
        $this->assertNotSame($key, $this->attributes($other, '//*[@data-block]', 'data-block-key')[0]);
    }

    public function testTheMenuOffersTheTypesTheFieldAllows(): void
    {
        $this->useBlocksField("    blocks: [cta, hero]\n");

        $page = $this->edit();

        $this->assertSame(['cta', 'hero'], $this->attributes($page, '//button[@data-add-type]', 'data-add-type'));
        $this->assertSame([url_to('content-block', 'sections', 'cta'), url_to('content-block', 'sections', 'hero')], $this->attributes($page, '//button[@data-add-type]', 'hx-get'));
    }

    public function testAddIsDisabledAtMaxItemsAndTheServerRefusesIt(): void
    {
        $this->useBlocksField("    max_items: 3\n");

        $page = $this->edit();

        $this->assertTrue($this->query($page, '//button[@data-add-block]')->item(0)->hasAttribute('disabled'));

        foreach ($this->attributes($page, '//button[@data-add-type]', 'hx-vals') as $vals) {
            $this->assertStringContainsString('max: 3', $vals);
        }

        $refused = $this->actingAs($this->admin)->get(route_to('content-block', 'sections', 'hero'), ['count' => 3, 'max' => 3]);
        $this->assertSame(422, $refused->response()->getStatusCode());
        $this->assertSame('', trim($refused->getBody()));

        $this->actingAs($this->admin)->get(route_to('content-block', 'sections', 'hero'), ['count' => 2, 'max' => 3])->assertOK();
    }

    public function testAddIsEnabledBelowMaxItems(): void
    {
        $this->useBlocksField("    max_items: 4\n");

        $this->assertFalse($this->query($this->edit(), '//button[@data-add-block]')->item(0)->hasAttribute('disabled'));
    }

    public function testSavingMoreBlocksThanMaxItemsIsRefusedAndWritesNothing(): void
    {
        $this->useBlocksField("    max_items: 3\n");

        $before = file_get_contents($this->site . 'collections/blog/2026-01-10.first-post.md');
        $fields = $this->formFields($this->editBody(), $this->action());

        $fields['fields']['sections']['b_aaaaaa'] = ['type' => 'hero', 'heading' => 'Fourth'];

        $this->actingAs($this->admin)->post($this->path(), $fields)->assertOK();

        $this->assertSame($before, file_get_contents($this->site . 'collections/blog/2026-01-10.first-post.md'));
    }

    public function testAFieldWithOneItemIsASlot(): void
    {
        $this->useBlocksField("    max_items: 1\n");
        $this->writeSections("sections:\n  - type: hero\n    heading: Only\n");

        $page = $this->edit();

        $this->assertCount(1, $this->query($page, '//*[@data-blocks][@data-slot]'));
        $this->assertCount(1, $this->query($page, '//*[@data-blocks-list]/*[@data-block]'));

        foreach (['data-handle', 'data-block-up', 'data-block-down', 'data-block-duplicate', 'data-block-toggle'] as $part) {
            $this->assertCount(0, $this->query($page, "//*[@data-block]//*[@{$part}]"), $part);
        }

        $this->assertCount(1, $this->query($page, '//*[@data-block]//*[@data-block-delete]'));
        $this->assertSame('{ open: true }', $this->attributes($page, '//*[@data-block]', 'x-data')[0]);

        foreach ($this->attributes($page, '//button[@data-add-type]', 'hx-get') as $url) {
            $this->assertStringContainsString('slot=1', $url);
        }

        foreach ($this->attributes($page, '//button[@data-add-type]', 'hx-swap') as $swap) {
            $this->assertSame('innerHTML', $swap);
        }

        $this->assertFalse($this->query($page, '//button[@data-add-block]')->item(0)->hasAttribute('disabled'));
    }

    public function testASlotFragmentHasNoListChrome(): void
    {
        $fragment = $this->html($this->actingAs($this->admin)->get(route_to('content-block', 'sections', 'hero'), ['slot' => '1']));

        $this->assertCount(1, $this->query($fragment, '//*[@data-block]'));
        $this->assertCount(0, $this->query($fragment, '//*[@data-handle]'));
        $this->assertCount(0, $this->query($fragment, '//*[@data-block-up]'));
    }

    public function testTheFragmentOfATypeThatDoesNotExistIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('content-block', 'sections', 'nope'));
    }

    public function testTheFragmentNeedsPermissionToEdit(): void
    {
        $viewer = $this->createUser();
        $viewer->addPermission('admin.access', 'content.access');

        $this->actingAs($viewer)->get(route_to('content-block', 'sections', 'hero'))->assertRedirectTo(site_url(ADMIN_AREA));
    }

    // Saving

    public function testReorderingDuplicatingAddingAndDeletingBlocksWritesThemInTheShownOrder(): void
    {
        $fields                    = $this->formFields($this->editBody(), $this->action());
        [$welcome, $cta, $goodbye] = array_values($fields['fields']['sections']);

        // What the cards post once the editor has moved Call to action up, deleted Goodbye, copied Welcome and added a hero.
        $added = $this->html($this->actingAs($this->admin)->get(route_to('content-block', 'sections', 'hero')));
        preg_match('/\[(b_[0-9a-f]{6})\]\[heading\]/', $this->attributes($added, '//input[@type="text"]', 'name')[0], $match);

        $fields['fields']['sections'] = [
            'b_000001' => $cta,
            'b_000002' => $welcome,
            'b_000003' => ['heading' => 'Welcome'] + $welcome,
            $match[1]  => ['type' => 'hero', 'heading' => 'Added'],
        ];

        $this->actingAs($this->admin)->post($this->path(), $fields)->assertRedirect();

        $saved = $this->saved();

        $this->assertSame(['cta', 'hero', 'hero', 'hero'], array_column($saved, 'type'));
        $this->assertSame(['Join', 'Welcome', 'Welcome', 'Added'], array_map(static fn (array $block): string => $block['label'] ?? $block['heading'], $saved));
        $this->assertTrue($saved[0]['new_tab']);
    }

    public function testTheKeysOfTheInputsAreNotSaved(): void
    {
        $fields = $this->formFields($this->editBody(), $this->action());

        $this->actingAs($this->admin)->post($this->path(), $fields)->assertRedirect();

        $this->assertStringNotContainsString('b_', (string) file_get_contents($this->site . 'collections/blog/2026-01-10.first-post.md'));
        $this->assertTrue(array_is_list($this->saved()));
    }

    public function testTheOrderComesFromThePostedOrderNotFromTheKeys(): void
    {
        $fields                       = $this->formFields($this->editBody(), $this->action());
        $blocks                       = $fields['fields']['sections'];
        $fields['fields']['sections'] = array_reverse($blocks, true);

        $this->actingAs($this->admin)->post($this->path(), $fields)->assertRedirect();

        $this->assertSame(['hero', 'cta', 'hero'], array_column($this->saved(), 'type'));
        $this->assertSame('Goodbye', $this->saved()[0]['heading']);
        $this->assertSame('Welcome', $this->saved()[2]['heading']);
    }

    // Custom field types and overridden partials

    public function testACustomFieldTypeRendersAndSavesInsideABlock(): void
    {
        config('Fields')->types = ['shout' => ShoutField::class];
        $this->writeBlock('loud', "title: Loud\nfields:\n  - handle: say\n    type: shout\n");

        $fragment = $this->html($this->actingAs($this->admin)->get(route_to('content-block', 'sections', 'loud')));
        $key      = $this->attributes($fragment, '//*[@data-block]', 'data-block-key')[0];

        $this->assertCount(1, $this->query($fragment, "//input[@type='text'][@name='fields[sections][{$key}][say]']"));

        $fields                             = $this->formFields($this->editBody(), $this->action());
        $fields['fields']['sections'][$key] = ['type' => 'loud', 'say' => 'hey'];

        $this->actingAs($this->admin)->post($this->path(), $fields)->assertRedirect();

        $this->assertSame('HEY', $this->saved()[3]['say']);
    }

    public function testAnAppsOwnInputPartialIsUsedInsideABlock(): void
    {
        $dir  = APPPATH . 'Views/fields';
        $file = $dir . '/text.php';
        $made = ! is_dir($dir) && mkdir($dir, 0777, true);
        file_put_contents($file, '<input type="text" name="<?= esc($name, "attr") ?>" data-overridden>');

        try {
            $fragment = $this->html($this->actingAs($this->admin)->get(route_to('content-block', 'sections', 'hero')));
            $page     = $this->edit();
        } finally {
            unlink($file);
            if ($made) {
                rmdir($dir);
            }
        }

        $this->assertCount(2, $this->query($fragment, '//*[@data-block]//input[@data-overridden][contains(@name, "fields[sections][b_")]'));
        $this->assertCount(4, $this->query($page, '//*[@data-block="hero"]//input[@data-overridden]'));
    }

    // Errors

    public function testAnErrorShowsInTheCardThatHasItAndOpensIt(): void
    {
        $fields = $this->formFields($this->editBody(), $this->action());
        $keys   = array_keys($fields['fields']['sections']);

        $fields['fields']['sections'][$keys[2]]['heading'] = '';

        $response = $this->actingAs($this->admin)->post($this->path(), $fields);
        $response->assertOK();

        $page  = $this->html($response);
        $cards = $this->query($page, '//*[@data-blocks-list]/*[@data-block]');

        $this->assertSame(3, $cards->length);
        $this->assertSame(['{ open: false }', '{ open: false }', '{ open: true }'], $this->attributes($page, '//*[@data-blocks-list]/*[@data-block]', 'x-data'));
        $this->assertCount(0, $this->query($page, '(//*[@data-block])[1]//*[@data-block-errors]'));
        $this->assertCount(0, $this->query($page, '(//*[@data-block])[2]//*[@data-block-errors]'));

        $error = $this->query($page, '(//*[@data-block])[3]//*[@data-block-errors]/*[@data-error="3.heading"]');
        $this->assertCount(1, $error);
        $this->assertStringStartsWith(lang('Content.blockError', [3, 'Hero', '']), trim($error->item(0)->textContent));
    }

    public function testTheTypedValuesAreKeptWhenTheFormComesBackWithAnError(): void
    {
        $fields = $this->formFields($this->editBody(), $this->action());
        $keys   = array_keys($fields['fields']['sections']);

        $fields['fields']['sections'][$keys[0]]['heading'] = '';
        $fields['fields']['sections'][$keys[1]]['label']   = 'Typed';

        $page = $this->html($this->actingAs($this->admin)->post($this->path(), $fields));

        $this->assertCount(1, $this->query($page, '//input[@value="Typed"][contains(@name, "[label]")]'));
        $this->assertSame(['hero', 'cta', 'hero'], $this->attributes($page, '//*[@data-blocks-list]/*[@data-block]', 'data-block'));
    }

    // Unknown block types

    public function testABlockOfATypeThatIsGoneIsAReadOnlyCardAndSurvivesASaveUnchanged(): void
    {
        $this->writeSections("sections:\n  - type: hero\n    heading: Welcome\n  - type: banner\n    image: hero.jpg\n    nested:\n      list: [1, 2]\n      flag: true\n    ratio: 1.5\n  - type: cta\n    label: Join\n");
        $original = $this->blocksInFile()[1];

        $body = $this->editBody();
        $page = $this->html($body);

        $this->assertCount(1, $this->query($page, '//*[@data-block="banner"]//*[@data-block-unknown]'));
        $this->assertCount(0, $this->query($page, '//*[@data-block="banner"]//input[@type="text"]'));

        $fields = $this->formFields($body, $this->action());

        // Reordering the card around the others must not change it either.
        $fields['fields']['sections'] = array_reverse($fields['fields']['sections'], true);

        $this->actingAs($this->admin)->post($this->path(), $fields)->assertRedirect();

        $saved = $this->blocksInFile();

        $this->assertSame(['cta', 'banner', 'hero'], array_column($saved, 'type'));
        $this->assertSame($original, $saved[1]);
        $this->assertEqualsWithDelta(1.5, $saved[1]['ratio'], PHP_FLOAT_EPSILON);
    }

    public function testAnUnknownBlockCannotBeMadeUpFromAPost(): void
    {
        $blueprint = Blueprint::load($this->blueprints, 'blog', 'post');
        $form      = new EntryForm($blueprint, true);

        // What the form shows for a block of a type that is gone, which is all a post can carry back.
        $shown = BlocksField::decode((new BlocksField())->fromStored([['type' => 'ghost', 'a' => 'b']], $blueprint->field('sections')))[0];

        $this->assertSame(['type', 'kept', 'signed'], array_keys($shown));

        $forged = [
            'no kept'          => ['type' => 'ghost'],
            'another type'     => ['type' => 'ghost', 'kept' => '{"type":"other"}'] + $shown,
            'not json'         => ['type' => 'ghost', 'kept' => 'not json'] + $shown,
            'changed'          => ['type' => 'ghost', 'kept' => '{"type":"ghost","a":"changed"}'] + $shown,
            'made up'          => ['type' => 'ghost', 'kept' => '{"type":"ghost","a":"b"}'],
            'type from signed' => ['type' => 'other'] + $shown,
        ];

        foreach ($forged as $why => $block) {
            $values = $form->prepare(['fields' => ['title' => 'T', 'sections' => [$block]]]);

            $this->assertArrayHasKey('sections.1', $form->validate($values), $why);
        }

        $values = $form->prepare(['fields' => ['title' => 'T', 'sections' => [$shown]]]);

        $this->assertSame([], $form->validate($values));
        $this->assertSame([['type' => 'ghost', 'a' => 'b']], $form->apply($values, [], '')[0]['sections']);
    }

    public function testWithoutAnEncryptionKeyNoUnknownBlockIsTrusted(): void
    {
        $blueprint = Blueprint::load($this->blueprints, 'blog', 'post');
        $form      = new EntryForm($blueprint, true);
        config('Encryption')->key = '';

        // The key is empty, so anyone could make this signature.
        $json   = '{"type":"ghost","a":"b"}';
        $forged = ['type' => 'ghost', 'kept' => $json, 'signed' => hash_hmac('sha256', 'content-block|' . $json, '')];
        $values = $form->prepare(['fields' => ['title' => 'T', 'sections' => [$forged]]]);

        $this->assertArrayHasKey('sections.1', $form->validate($values));
    }

    public function testAKeptBlockIsIgnoredWhenTheTypeHasABlueprint(): void
    {
        $form   = new EntryForm(Blueprint::load($this->blueprints, 'blog', 'post'), true);
        $values = $form->prepare(['fields' => ['title' => 'T', 'sections' => [['type' => 'hero', 'heading' => 'Hi', 'kept' => '{"type":"hero","heading":"Sneaky","extra":"x"}']]]]);

        $this->assertSame([['type' => 'hero', 'heading' => 'Hi']], $form->apply($values, [], '')[0]['sections']);
    }

    // Helpers

    private function writeBlock(string $type, string $yaml): void
    {
        file_put_contents($this->blueprints . "blocks/{$type}.yaml", $yaml);
    }

    private function useBlocksField(string $options = ''): void
    {
        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n  - handle: sections\n    type: blocks\n{$options}",
        );
    }

    private function writeSections(string $frontMatter): void
    {
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: " . self::FIRST . "\ntitle: First post\n{$frontMatter}\n---\nThe first *post*.\n");
        (new Indexer($this->site))->reindex();
    }

    private function path(): string
    {
        return route_to('content-edit', 'blog', self::FIRST);
    }

    private function action(): string
    {
        return url_to('content-edit', 'blog', self::FIRST);
    }

    private function edit(): DOMXPath
    {
        return $this->html($this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST)));
    }

    private function editBody(): string
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', self::FIRST));
        $response->assertOK();

        return $response->getBody();
    }

    /**
     * @return list<array<string, mixed>> the blocks the entry has now
     */
    private function saved(): array
    {
        return Entry::fromRow((new ContentEntryModel())->find(self::FIRST))->data['sections'];
    }

    /**
     * @return list<array<string, mixed>> the blocks as the file holds them now
     */
    private function blocksInFile(): array
    {
        preg_match('/\A---\n(.*?)\n---\n/s', (string) file_get_contents($this->site . 'collections/blog/2026-01-10.first-post.md'), $match);

        return Yaml::parse($match[1])['sections'];
    }

    private function html(string|TestResponse $page): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . ($page instanceof TestResponse ? $page->getBody() : $page));
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function query(DOMXPath $page, string $xpath): DOMNodeList
    {
        return $page->query($xpath);
    }

    /**
     * @return list<string>
     */
    private function attributes(DOMXPath $page, string $xpath, string $attribute): array
    {
        $values = [];

        foreach ($page->query($xpath) as $node) {
            $values[] = $node->getAttribute($attribute);
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private function texts(DOMXPath $page, string $xpath): array
    {
        $values = [];

        foreach ($page->query($xpath) as $node) {
            $values[] = trim($node->textContent);
        }

        return $values;
    }
}
