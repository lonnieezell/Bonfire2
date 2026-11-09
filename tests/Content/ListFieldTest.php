<?php

namespace Tests\Content;

use Bonfire\Content\Block;
use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\EntryForm;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Content\ContentTestCase;

/**
 * The list field type: its blueprint check, saving its rows, and reading them back.
 *
 * @internal
 */
final class ListFieldTest extends ContentTestCase
{
    private const FIRST = '11111111-1111-4111-8111-111111111111';
    private const ROWS  = "    fields:\n      - {handle: title, type: text, validate: required}\n      - {handle: stars, type: integer, min: 1}\n      - {handle: live, type: toggle}\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->useListField(self::ROWS);
    }

    // The blueprint check

    public function testABlueprintLoadsAListFieldWithItsSubFields(): void
    {
        $field = $this->blueprint()->field('features');

        $this->assertSame('list', $field->type);
        $this->assertSame(['title', 'stars', 'live'], array_map(static fn ($sub): string => $sub->handle, Blueprint::subFields($field)));
    }

    #[DataProvider('provideASubFieldOfTheseTypesFailsTheBlueprint')]
    public function testASubFieldOfTheseTypesFailsTheBlueprint(string $definition): void
    {
        $this->useListField("    fields:\n      - {$definition}\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage("a list can't have");

        $this->blueprint();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideASubFieldOfTheseTypesFailsTheBlueprint(): iterable
    {
        yield 'slug' => ['{handle: nick, type: slug}'];

        yield 'terms' => ['{handle: tags, type: terms, taxonomy: tags}'];

        yield 'blocks' => ['{handle: inner, type: blocks}'];

        yield 'list' => ['{handle: inner, type: list, fields: [{handle: a, type: text}]}'];
    }

    #[DataProvider('provideAListWithoutUsableSubFieldsFailsTheBlueprint')]
    public function testAListWithoutUsableSubFieldsFailsTheBlueprint(string $options): void
    {
        $this->useListField($options);

        $this->expectException(ContentException::class);

        $this->blueprint();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAListWithoutUsableSubFieldsFailsTheBlueprint(): iterable
    {
        yield 'no fields' => [''];

        yield 'empty fields' => ["    fields: []\n"];

        yield 'not a list' => ["    fields: nope\n"];

        yield 'a repeated handle' => ["    fields:\n      - {handle: a, type: text}\n      - {handle: a, type: text}\n"];

        yield 'an unknown type' => ["    fields:\n      - {handle: a, type: nope}\n"];
    }

    public function testASubFieldCanUseHandlesAnEntryKeepsForItself(): void
    {
        $this->useListField("    fields:\n      - {handle: type, type: text}\n      - {handle: status, type: text}\n");

        $this->assertCount(2, Blueprint::subFields($this->blueprint()->field('features')));
    }

    // Saving

    public function testRowsAreSavedAsAListOfMapsInThePostedOrderWithTheirValuesTyped(): void
    {
        $this->assertSame(
            [['title' => 'One', 'stars' => 4, 'live' => true], ['title' => 'Two', 'live' => false]],
            $this->saved(['z' => ['title' => 'One', 'stars' => '4', 'live' => '1'], 'a' => ['title' => 'Two', 'stars' => '']]),
        );
    }

    public function testAKeyTheSubFieldsDoNotListIsNotSavedAndAnItemThatIsNotARowIsDropped(): void
    {
        $this->assertSame([['title' => 'One', 'live' => false]], $this->saved(['a' => ['title' => 'One', 'sneaky' => 'x'], 'b' => 'not a row']));
    }

    public function testNoRowsAddsNoKeyToTheFile(): void
    {
        $this->assertArrayNotHasKey('features', $this->frontMatter(null));
    }

    public function testWhatTheFileHoldsComesBackIntoTheFormAsText(): void
    {
        $form   = $this->form();
        $values = $form->valuesOf(['features' => [['title' => 'One', 'stars' => 4, 'live' => true, 'extra' => 'x'], 'not a row']], '', '');

        $this->assertSame([['title' => 'One', 'stars' => '4', 'live' => '1']], json_decode($values['features'], true));
    }

    public function testANewEntryHasNoRows(): void
    {
        $this->assertSame('', $this->form()->emptyValues()['features']);
    }

    // Validating

    public function testEachSubFieldIsValidatedByItsTypeAndItsRulesAndLabelledByItsRow(): void
    {
        $errors = $this->errors(['a' => ['title' => 'Fine', 'stars' => '2'], 'b' => ['title' => '', 'stars' => '0']]);

        $this->assertArrayHasKey('features', $errors);
        $this->assertArrayHasKey('features.2.title', $errors);
        $this->assertArrayHasKey('features.2.stars', $errors);
        $this->assertArrayNotHasKey('features.1.title', $errors);
    }

    public function testValidRowsHaveNoErrors(): void
    {
        $this->assertSame([], $this->errors(['a' => ['title' => 'Fine', 'stars' => '2']]));
    }

    public function testMaxItemsIsEnforced(): void
    {
        $this->useListField(self::ROWS . "    max_items: 1\n");

        $this->assertSame([], $this->errors(['a' => ['title' => 'One']]));
        $this->assertArrayHasKey('features', $this->errors(['a' => ['title' => 'One'], 'b' => ['title' => 'Two']]));
    }

    public function testMinItemsIsEnforcedEvenWithNoRows(): void
    {
        $this->useListField(self::ROWS . "    min_items: 2\n");

        $this->assertArrayHasKey('features', $this->errors(null));
        $this->assertArrayHasKey('features', $this->errors(['a' => ['title' => 'One']]));
        $this->assertSame([], $this->errors(['a' => ['title' => 'One'], 'b' => ['title' => 'Two']]));
    }

    // Reading

    public function testFieldReturnsAListOfMapsCastByTheSubFieldTypes(): void
    {
        $this->assertSame(
            [['title' => 'One', 'stars' => 4, 'live' => true], ['title' => 'Two', 'stars' => null, 'live' => false]],
            $this->entryWith("features:\n  - {title: One, stars: '4', live: 1}\n  - {title: Two, extra: x}\n")->field('features'),
        );
    }

    public function testFieldGivesTheDefaultWhenTheEntryHasNoRows(): void
    {
        $this->assertSame('none', $this->entryWith('')->field('features', 'none'));
    }

    public function testFieldLeavesOtherListsAsTheFileHasThem(): void
    {
        $entry = $this->entryWith("other:\n  - {stars: '4'}\n");

        $this->assertSame([['stars' => '4']], $entry->field('other'));
    }

    public function testFieldGivesTheRawRowsWhenTheBlueprintIsGone(): void
    {
        $entry = $this->entryWith("features:\n  - {stars: '4'}\n");

        unlink($this->blueprints . 'collections/blog/post.yaml');

        $this->assertSame([['stars' => '4']], $entry->field('features'));
    }

    public function testFieldCastsTheRowsWhenTheFirstItemIsNotAMap(): void
    {
        $this->assertSame(
            [['title' => null, 'stars' => 4, 'live' => false]],
            $this->entryWith("features:\n  - stray\n  - {stars: '4'}\n")->field('features'),
        );
    }

    public function testAListInsideABlockSavesAndReadsBackCast(): void
    {
        mkdir($this->blueprints . 'blocks');
        file_put_contents($this->blueprints . 'blocks/pricing.yaml', "title: Pricing\nfields:\n  - handle: tiers\n    type: list\n    max_items: 2\n" . self::ROWS);
        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n  - handle: slug\n    type: slug\n  - handle: sections\n    type: blocks\n",
        );

        $form   = $this->form();
        $values = $form->prepare(['fields' => ['title' => 'T', 'sections' => ['b_1' => ['type' => 'pricing', 'tiers' => ['r1' => ['title' => 'Basic', 'stars' => '5', 'live' => '1']]]]]]);

        $this->assertSame([], $form->validate($values));
        $this->assertSame([['type' => 'pricing', 'tiers' => [['title' => 'Basic', 'stars' => 5, 'live' => true]]]], $form->apply($values, [], '')[0]['sections']);

        $block = $this->entryWith("sections:\n  - type: pricing\n    tiers:\n      - {title: Basic, stars: '5', live: 1}\n")->block('sections');
        $this->assertInstanceOf(Block::class, $block);

        $this->assertSame([['title' => 'Basic', 'stars' => 5, 'live' => true]], $block->field('tiers'));
    }

    public function testAnErrorInARowOfAListInsideABlockFailsTheBlock(): void
    {
        mkdir($this->blueprints . 'blocks');
        file_put_contents($this->blueprints . 'blocks/pricing.yaml', "title: Pricing\nfields:\n  - handle: tiers\n    type: list\n" . self::ROWS);
        file_put_contents($this->blueprints . 'collections/blog/post.yaml', "title: Post\nfields:\n  - handle: sections\n    type: blocks\n");

        $form   = $this->form();
        $values = $form->prepare(['fields' => ['sections' => ['b_1' => ['type' => 'pricing', 'tiers' => ['r1' => ['title' => '']]]]]]);

        $this->assertArrayHasKey('sections.1.tiers', $form->validate($values));
    }

    // Helpers

    /**
     * @param array<array-key, mixed>|null $rows what the rows' inputs post, or null for none
     *
     * @return array<array-key, mixed> the entry's front matter
     */
    private function frontMatter(?array $rows): array
    {
        $form = $this->form();

        return $form->apply($form->prepare(['fields' => ['title' => 'T', 'features' => $rows]]), [], '')[0];
    }

    /**
     * @param array<array-key, mixed> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function saved(array $rows): array
    {
        return $this->frontMatter($rows)['features'];
    }

    /**
     * @param array<array-key, mixed>|null $rows
     *
     * @return array<string, string>
     */
    private function errors(?array $rows): array
    {
        $form = $this->form();

        return $form->validate($form->prepare(['fields' => ['title' => 'T', 'features' => $rows]]));
    }

    private function form(): EntryForm
    {
        return new EntryForm($this->blueprint(), true);
    }

    private function entryWith(string $frontMatter): Entry
    {
        $this->writeFile('collections/blog/2026-01-10.first-post.md', "---\nid: " . self::FIRST . "\ntitle: First post\n{$frontMatter}---\nThe first *post*.\n");
        (new Indexer($this->site))->reindex();

        return Entry::fromRow((new ContentEntryModel())->find(self::FIRST));
    }

    private function useListField(string $options): void
    {
        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n  - handle: features\n    type: list\n{$options}",
        );
    }

    private function blueprint(): Blueprint
    {
        return Blueprint::load($this->blueprints, 'blog', 'post');
    }
}
