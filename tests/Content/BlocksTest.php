<?php

namespace Tests\Content;

use Bonfire\Content\Block;
use Bonfire\Content\BlockList;
use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\EntryForm;
use Bonfire\Content\Fields\BlocksField;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\SubmitsForms;

/**
 * The blocks field type: block types in resources/blueprints/blocks, and rendering a blocks field through partials.
 *
 * @internal
 */
final class BlocksTest extends ContentTestCase
{
    use SubmitsForms;

    private const FIRST = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        config('Themes')->componentsLookupPaths = [SUPPORTPATH . 'Views/Components/'];

        mkdir($this->blueprints . 'blocks');
        $this->writeBlock('hero', "title: Hero\nicon: star\nfields:\n  - handle: heading\n    type: text\n    validate: required|max_length[20]\n  - handle: subheading\n    type: text\n");
        $this->writeBlock('cta', "title: Call to action\nfields:\n  - handle: label\n    type: text\n  - handle: new_tab\n    type: toggle\n  - handle: count\n    type: integer\n    min: 1\n");

        $this->useSections();
        $this->writeSections(<<<'YAML'
            sections:
              - type: hero
                heading: Welcome
              - type: cta
                label: Join
                new_tab: true
                count: "3"
              - type: hero
                heading: Goodbye
            YAML);
    }

    // Reading

    public function testEachBlockRendersThroughItsPartialInOrder(): void
    {
        $html = $this->entry()->renderBlocks('sections');

        preg_match_all('/data-block="(\w+)"/', $html, $matches);

        $this->assertSame(['hero', 'cta', 'hero'], $matches[1]);
        $this->assertLessThan(strpos($html, 'Goodbye'), strpos($html, 'Welcome'));
    }

    public function testComponentsWorkInsideAPartial(): void
    {
        $html = $this->entry()->blocks('sections')->first()->render();

        $this->assertStringContainsString('Hello Welcome', $html);
        $this->assertStringNotContainsString('<x-greeting', $html);
    }

    public function testAPartialDoesNotKeepTheDataOfAnEarlierBlock(): void
    {
        // CodeIgniter's view() keeps data between calls, so a block without a value must not show the last one's.
        $this->writeSections("sections:\n  - type: hero\n    heading: First\n  - type: hero\n    subheading: no heading\n");

        $this->assertStringContainsString('No heading', $this->entry()->renderBlocks('sections'));
    }

    public function testBlockReturnsTheFirstBlockOrNull(): void
    {
        $entry = $this->entry();

        $this->assertInstanceOf(Block::class, $entry->block('sections'));
        $this->assertSame('hero', $entry->block('sections')->type);
        $this->assertSame('Welcome', $entry->block('sections')->field('heading'));
        $this->assertNull($entry->block('missing'));
        $this->assertNull($entry->block('title'));
    }

    public function testBlocksIsAnIterableCountableList(): void
    {
        $blocks = $this->entry()->blocks('sections');

        $this->assertInstanceOf(BlockList::class, $blocks);
        $this->assertCount(3, $blocks);
        $this->assertSame(['hero', 'cta', 'hero'], array_map(static fn (Block $block): string => $block->type, iterator_to_array($blocks)));
    }

    public function testOfTypeFilters(): void
    {
        $blocks = $this->entry()->blocks('sections');

        $this->assertCount(2, $blocks->ofType('hero'));
        $this->assertSame('cta', $blocks->ofType('cta')->first()->type);
        $this->assertCount(3, $blocks->ofType('hero', 'cta'));
        $this->assertCount(0, $blocks->ofType('nope'));
        $this->assertNull($blocks->ofType('nope')->first());
        $this->assertSame('', $blocks->ofType('nope')->render());
        $this->assertSame(2, substr_count($blocks->ofType('hero')->render(), 'data-block="hero"'));
    }

    public function testAFieldThatIsMissingOrNotAListIsAnEmptyList(): void
    {
        $entry = $this->entry();

        $this->assertCount(0, $entry->blocks('nothing_here'));
        $this->assertCount(0, $entry->blocks('title'));
        $this->assertSame('', $entry->renderBlocks('nothing_here'));
    }

    public function testItemsThatAreNotBlocksAreSkipped(): void
    {
        $this->writeSections("sections:\n  - just text\n  - heading: no type\n  - type: 5\n  - type: hero\n    heading: Real\n");

        $blocks = $this->entry()->blocks('sections');

        $this->assertCount(1, $blocks);
        $this->assertSame('Real', $blocks->first()->field('heading'));
    }

    public function testFieldCastsByTheBlockBlueprint(): void
    {
        $cta = $this->entry()->blocks('sections')->ofType('cta')->first();
        $this->assertInstanceOf(Block::class, $cta);

        $this->assertTrue($cta->field('new_tab'));
        $this->assertSame(3, $cta->field('count'));
        $this->assertSame('Join', $cta->field('label'));
    }

    public function testFieldGivesTheDefaultForAMissingOrEmptyValue(): void
    {
        $hero = $this->entry()->block('sections');
        $this->assertInstanceOf(Block::class, $hero);

        $this->assertNull($hero->field('subheading'));
        $this->assertSame('fallback', $hero->field('subheading', 'fallback'));

        $this->writeSections("sections:\n  - type: cta\n    new_tab: false\n    count: lots\n    label: ''\n");
        $cta = $this->entry()->block('sections');
        $this->assertInstanceOf(Block::class, $cta);

        $this->assertFalse($cta->field('new_tab', 'default'));
        $this->assertSame(7, $cta->field('count', 7));
        $this->assertSame('x', $cta->field('label', 'x'));
    }

    public function testAValueTheBlueprintDoesNotListIsReturnedAsTheFileHoldsIt(): void
    {
        $this->writeSections("sections:\n  - type: hero\n    heading: H\n    extra: [a, b]\n");

        $this->assertSame(['a', 'b'], $this->entry()->block('sections')->field('extra'));
    }

    public function testABlockOfATypeWhoseFileIsGoneStillReadsRaw(): void
    {
        unlink($this->blueprints . 'blocks/cta.yaml');

        $cta = $this->entry()->blocks('sections')->ofType('cta')->first();
        $this->assertInstanceOf(Block::class, $cta);

        $this->assertSame('3', $cta->field('count'));
        $this->assertTrue($cta->field('new_tab'));
    }

    public function testABlockKnowsItsEntry(): void
    {
        $entry = $this->entry();

        $this->assertSame($entry->id, $entry->block('sections')->entry->id);
        $this->assertStringContainsString('data-entry="first-post"', $entry->renderBlocks('sections'));
    }

    // Partials

    public function testAMissingPartialThrowsOutsideProductionNamingTheFile(): void
    {
        $this->writeBlock('quote', "title: Quote\nfields:\n  - handle: text\n    type: text\n");
        $this->writeSections("sections:\n  - type: quote\n    text: Hi\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('blocks/quote.php');

        $this->entry()->renderBlocks('sections');
    }

    public function testAMissingPartialIsLoggedAndRendersNothingInProduction(): void
    {
        $this->writeBlock('quote', "title: Quote\nfields:\n  - handle: text\n    type: text\n");
        $this->writeSections("sections:\n  - type: quote\n    text: Hi\n  - type: hero\n    heading: Kept\n");

        $blocks = $this->entry()->blocks('sections');

        $this->assertSame('', $blocks->first()->renderFor(true));
        $this->assertLogged('error', 'Content: block partial blocks/quote.php is missing, so a quote block was not rendered.');
        $this->assertStringContainsString('Kept', $blocks->renderFor(true));
    }

    public function testThePartialFolderIsConfigurable(): void
    {
        config('Content')->blockViewPath = 'elsewhere';

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('elsewhere/hero.php');

        $this->entry()->block('sections')->render();
    }

    public function testATypeThatCouldEscapeTheFolderNeverReachesTheFilesystem(): void
    {
        $this->writeSections("sections:\n  - type: ../Components/badge\n");

        $this->expectException(ContentException::class);

        $this->entry()->block('sections')->render();
    }

    // Blueprint checks

    public function testABlockTypeWithoutAFileFailsTheBlueprint(): void
    {
        $this->useSections("    blocks: [hero, banner]\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('`sections`');
        $this->expectExceptionMessage('banner');
        $this->expectExceptionMessage('blocks/banner.yaml');

        $this->blueprint();
    }

    public function testABlocksFieldWithoutAnyBlockTypeFailsTheBlueprint(): void
    {
        unlink($this->blueprints . 'blocks/hero.yaml');
        unlink($this->blueprints . 'blocks/cta.yaml');

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('no block types');

        $this->blueprint();
    }

    #[DataProvider('provideABlockFieldOfTheseTypesFailsTheBlueprint')]
    public function testABlockFieldOfTheseTypesFailsTheBlueprint(string $field, string $message): void
    {
        $this->writeBlock('hero', "title: Hero\nfields:\n{$field}");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage($message);

        $this->blueprint();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideABlockFieldOfTheseTypesFailsTheBlueprint(): iterable
    {
        yield 'slug' => ["  - handle: nick\n    type: slug\n", 'slug'];

        yield 'terms' => ["  - handle: tags\n    type: terms\n    taxonomy: tags\n", 'terms'];

        yield 'blocks' => ["  - handle: inner\n    type: blocks\n", 'blocks'];

        yield 'the handle type' => ["  - handle: type\n    type: text\n", '`type`'];
    }

    public function testABlocksFieldOfOneBlockTypeCannotHideItselfInItsOwnBlock(): void
    {
        // A block that holds blocks would never end, so the check refuses it before it loads the types it lists.
        $this->writeBlock('hero', "title: Hero\nfields:\n  - handle: inner\n    type: blocks\n    blocks: [hero]\n");

        $this->expectException(ContentException::class);

        $this->blueprint();
    }

    public function testTheBlueprintKnowsTheTypesTheFieldAllows(): void
    {
        $this->assertSame(['cta', 'hero'], BlocksField::types($this->blueprint()->field('sections')));

        $this->useSections("    blocks: [hero]\n");
        $this->assertSame(['hero'], BlocksField::types($this->blueprint()->field('sections')));
    }

    public function testABlockBlueprintLoadsItsTitleIconAndFields(): void
    {
        $hero = Blueprint::loadBlock($this->blueprints, 'hero');

        $this->assertSame('Hero', $hero->title);
        $this->assertSame('star', $hero->icon);
        $this->assertSame(['heading', 'subheading'], array_map(static fn ($field): string => $field->handle, $hero->fields));
        $this->assertNull(Blueprint::loadBlock($this->blueprints, 'cta')->icon);
    }

    // Saving

    public function testAValidBlocksFieldIsSavedAsAListOfMapsWithATypeKey(): void
    {
        $form   = $this->form();
        $values = $form->prepare($this->posting([
            ['type' => 'hero', 'heading' => 'Hi', 'ignored' => 'x'],
            ['type' => 'cta', 'label' => 'Go', 'new_tab' => 'on', 'count' => '2'],
        ]));

        $this->assertSame([], $form->validate($values));

        [$frontMatter] = $form->apply($values, [], '');

        $this->assertSame([
            ['type' => 'hero', 'heading' => 'Hi'],
            ['type' => 'cta', 'label' => 'Go', 'new_tab' => true, 'count' => 2],
        ], $frontMatter['sections']);
    }

    public function testSavedBlocksComeBackIntoTheForm(): void
    {
        $form   = $this->form();
        $values = $form->prepare($this->posting([['type' => 'cta', 'label' => 'Go', 'new_tab' => '1', 'count' => '2']]));

        [$frontMatter] = $form->apply($values, [], '');

        $this->assertSame($values['sections'], $form->valuesOf($frontMatter, '', 'x')['sections']);
    }

    public function testPostedBlocksInAnyKeyOrderKeepTheirOrder(): void
    {
        $form   = $this->form();
        $values = $form->prepare($this->posting([5 => ['type' => 'hero', 'heading' => 'B'], 2 => ['type' => 'hero', 'heading' => 'A']]));

        [$frontMatter] = $form->apply($values, [], '');

        $this->assertSame(['B', 'A'], array_column($frontMatter['sections'], 'heading'));
    }

    public function testASpaceAroundAPostedTypeDoesNotDropTheBlocksFieldsOrTheirRules(): void
    {
        $form   = $this->form();
        $values = $form->prepare($this->posting([['type' => ' hero ', 'heading' => '']]));

        $this->assertArrayHasKey('sections.1.heading', $form->validate($values));

        $values = $form->prepare($this->posting([['type' => ' hero ', 'heading' => 'Hi']]));

        $this->assertSame([['type' => 'hero', 'heading' => 'Hi']], $form->apply($values, [], '')[0]['sections']);
    }

    public function testAFileNameThatIsNotAValidTypeIsNotOfferedAsOne(): void
    {
        $this->writeBlock('call.to.action', "title: Odd\nfields:\n  - handle: label\n    type: text\n");

        $this->assertSame(['cta', 'hero'], BlocksField::types($this->blueprint()->field('sections')));
    }

    public function testAFieldLeftEmptyAddsNoKey(): void
    {
        $form   = $this->form();
        $values = $form->prepare(['fields' => ['title' => 'T']]);

        $this->assertSame([], $form->validate($values));
        $this->assertArrayNotHasKey('sections', $form->apply($values, [], '')[0]);
    }

    public function testMaxItemsIsEnforcedOnSave(): void
    {
        $this->useSections("    max_items: 2\n");
        $form = $this->form();
        $hero = ['type' => 'hero', 'heading' => 'H'];

        $this->assertSame([], $form->validate($form->prepare($this->posting([$hero, $hero]))));

        $errors = $form->validate($form->prepare($this->posting([$hero, $hero, $hero])));

        $this->assertSame(lang('Content.blocksTooMany', [2]), $errors['sections']);
    }

    public function testMinItemsIsEnforcedOnSaveEvenForAnEmptyField(): void
    {
        $this->useSections("    min_items: 2\n");
        $form = $this->form();
        $hero = ['type' => 'hero', 'heading' => 'H'];

        $this->assertSame(lang('Content.blocksTooFew', [2]), $form->validate($form->prepare(['fields' => ['title' => 'T']]))['sections']);
        $this->assertSame(lang('Content.blocksTooFew', [2]), $form->validate($form->prepare($this->posting([$hero])))['sections']);
        $this->assertSame([], $form->validate($form->prepare($this->posting([$hero, $hero]))));
    }

    public function testABlockFieldIsValidatedWithItsOwnRulesAndKeyedByPosition(): void
    {
        $form = $this->form();

        $errors = $form->validate($form->prepare($this->posting([
            ['type' => 'hero', 'heading' => 'Fine'],
            ['type' => 'hero', 'heading' => ''],
            ['type' => 'cta', 'label' => 'x', 'count' => '0'],
            ['type' => 'hero', 'heading' => str_repeat('a', 21)],
        ])));

        $this->assertArrayHasKey('sections.2.heading', $errors);
        $this->assertArrayHasKey('sections.3.count', $errors);
        $this->assertArrayHasKey('sections.4.heading', $errors);
        $this->assertArrayNotHasKey('sections.1.heading', $errors);
        $this->assertSame(lang('Fields.integerMin', [1]), $errors['sections.3.count']);
        // The field itself has an error too, which names the position the editor sees.
        $this->assertStringStartsWith(lang('Content.blockError', [2, 'Hero', $errors['sections.2.heading']]), $errors['sections']);
    }

    public function testABlockOfAnUnknownOrNotAllowedTypeIsRefused(): void
    {
        $this->useSections("    blocks: [hero]\n");
        $form = $this->form();

        $errors = $form->validate($form->prepare($this->posting([
            ['type' => 'hero', 'heading' => 'H'],
            ['type' => 'cta', 'label' => 'x'],
            ['type' => 'nope'],
            ['heading' => 'no type'],
        ])));

        $this->assertSame(lang('Content.blockTypeInvalid', ['cta']), $errors['sections.2']);
        $this->assertSame(lang('Content.blockTypeInvalid', ['nope']), $errors['sections.3']);
        $this->assertArrayHasKey('sections.4', $errors);
        $this->assertArrayNotHasKey('sections.1', $errors);
        $this->assertArrayHasKey('sections', $errors);
    }

    public function testNothingThatIsNotAListOfBlocksIsKept(): void
    {
        $form = $this->form();

        foreach (['just text', ['not a block', 5, null]] as $junk) {
            $values = $form->prepare(['fields' => ['title' => 'T', 'sections' => $junk]]);

            $this->assertSame('', $values['sections']);
            $this->assertSame([], $form->validate($values));
        }
    }

    public function testAStoredValueThatIsNotAListOfBlocksGivesAnEmptyFormValue(): void
    {
        $this->assertSame('', $this->form()->valuesOf(['sections' => 'text'], '', 'x')['sections']);
        $this->assertSame('', $this->form()->valuesOf(['sections' => [['heading' => 'no type']]], '', 'x')['sections']);
    }

    // Through the admin

    public function testSavingTheAdminFormKeepsTheBlocksTheFileHas(): void
    {
        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        service('routes')->loadRoutes();
        (new Indexer($this->site))->reindex();

        $admin = $this->createUser();
        $admin->addGroup('superadmin');

        $edit = $this->actingAs($admin)->get(route_to('content-edit', 'blog', self::FIRST));
        $edit->assertOK();
        $fields = $this->formFields($edit->getBody(), url_to('content-edit', 'blog', self::FIRST));

        $this->assertArrayHasKey('sections', $fields['fields']);

        $this->actingAs($admin)->post(route_to('content-edit', 'blog', self::FIRST), $fields)->assertRedirect();

        $saved = Entry::fromRow((new ContentEntryModel())->find(self::FIRST));

        $this->assertSame(['hero', 'cta', 'hero'], array_column($saved->data['sections'], 'type'));
        $this->assertSame(['Welcome', 'Goodbye'], array_values(array_filter(array_column($saved->data['sections'], 'heading'))));
        $this->assertTrue($saved->data['sections'][1]['new_tab']);
        $this->assertSame(3, $saved->data['sections'][1]['count']);
    }

    // Helpers

    private function writeBlock(string $type, string $yaml): void
    {
        file_put_contents($this->blueprints . "blocks/{$type}.yaml", $yaml);
    }

    /**
     * Gives the post blueprint a `sections` blocks field.
     *
     * @param string $options more lines for the field, indented four spaces
     */
    private function useSections(string $options = ''): void
    {
        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n  - handle: sections\n    type: blocks\n{$options}",
        );
    }

    /**
     * Gives the first post these front matter lines, and reindexes.
     */
    private function writeSections(string $frontMatter): void
    {
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: " . self::FIRST . "\ntitle: First post\n{$frontMatter}\n---\nThe first *post*.\n");
        (new Indexer($this->site))->reindex();
    }

    private function entry(): Entry
    {
        $entry = content()->find(self::FIRST);
        $this->assertInstanceOf(Entry::class, $entry);

        return $entry;
    }

    private function blueprint(): Blueprint
    {
        return Blueprint::load($this->blueprints, 'blog', 'post');
    }

    private function form(): EntryForm
    {
        return new EntryForm($this->blueprint(), true);
    }

    /**
     * @param array<array-key, mixed> $blocks
     *
     * @return array<string, mixed>
     */
    private function posting(array $blocks): array
    {
        return ['fields' => ['title' => 'T', 'sections' => $blocks]];
    }
}
