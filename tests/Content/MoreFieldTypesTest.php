<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\EntryForm;
use Bonfire\Content\Field;
use Bonfire\Content\Fields\EntriesField;
use Bonfire\Content\Fields\FieldTypes;
use Bonfire\Content\Fields\IntegerField;
use Bonfire\Content\Indexer;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Users\User;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\Content\ShoutField;
use Tests\Support\SubmitsForms;

/**
 * The select, integer, datetime and entries field types, and field types an app registers.
 *
 * @internal
 */
final class MoreFieldTypesTest extends ContentTestCase
{
    use SubmitsForms;

    private const ABOUT   = '55555555-5555-4555-8555-555555555555';
    private const CONTACT = '66666666-6666-4666-8666-666666666666';
    private const OLD     = '88888888-8888-4888-8888-888888888888';
    private const FIRST   = '11111111-1111-4111-8111-111111111111';
    private const FIELDS  = <<<'YAML'
          - handle: mood
            type: select
            options: [happy, sad]
          - handle: genre
            type: select
            options:
              sf: Science fiction
              fan: Fantasy
          - handle: tags
            type: select
            multiple: true
            options: [php, yaml, css]
          - handle: rating
            type: integer
            min: 1
            max: 5
          - handle: starts_at
            type: datetime
          - handle: related
            type: entries
            collections: [pages]
            max_items: 2
          - handle: see_also
            type: entries
            max_items: 1
          - handle: anything
            type: entries
        YAML;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        // route_to() runs before the first request, and another test class may have left the routes unloaded.
        service('routes')->loadRoutes();

        $this->writeFile('collections/pages.yaml', "title: Pages\n");
        mkdir($this->site . 'collections/pages');

        foreach (['about' => self::ABOUT, 'contact' => self::CONTACT, 'old' => self::OLD] as $slug => $id) {
            $this->writeFile("collections/pages/{$slug}.md", "---\nid: {$id}\ntitle: " . ucfirst($slug) . " page\n---\nBody.\n");
        }

        $this->useFields(self::FIELDS);
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

    // select

    public function testASelectKeepsOneOptionAsAString(): void
    {
        $form = $this->form();

        $values = $form->prepare(['fields' => ['title' => 'T', 'mood' => ' happy ', 'genre' => 'sf']]);

        $this->assertSame([], $form->validate($values));
        [$frontMatter] = $form->apply($values, [], '');
        $this->assertSame('happy', $frontMatter['mood']);
        $this->assertSame('sf', $frontMatter['genre']);
        $this->assertSame('sf', $form->valuesOf($frontMatter, '', 'x')['genre']);
    }

    public function testASelectRefusesAValueThatIsNotAnOptionOrIsAMapLabel(): void
    {
        $form = $this->form();

        foreach (['mood' => 'angry', 'genre' => 'Science fiction'] as $handle => $posted) {
            $errors = $form->validate($form->prepare(['fields' => ['title' => 'T', $handle => $posted]]));

            $this->assertSame(lang('Content.selectInvalid'), $errors[$handle] ?? null, $handle);
        }
    }

    public function testASelectLeftEmptyAddsNoKey(): void
    {
        $form   = $this->form();
        $values = $form->prepare(['fields' => ['title' => 'T']]);

        $this->assertSame([], $form->validate($values));
        $this->assertArrayNotHasKey('mood', $form->apply($values, [], '')[0]);
        $this->assertArrayNotHasKey('tags', $form->apply($values, [], '')[0]);
    }

    public function testAMultipleSelectKeepsAListAndRefusesAnyOptionThatIsNotOffered(): void
    {
        $form = $this->form();

        $values = $form->prepare(['fields' => ['title' => 'T', 'tags' => ['php', 'css', 'php', '']]]);
        $this->assertSame([], $form->validate($values));
        [$frontMatter] = $form->apply($values, [], '');
        $this->assertSame(['php', 'css'], $frontMatter['tags']);
        $this->assertSame($values['tags'], $form->valuesOf($frontMatter, '', 'x')['tags']);

        $errors = $form->validate($form->prepare(['fields' => ['title' => 'T', 'tags' => ['php', 'js']]]));
        $this->assertSame(lang('Content.selectInvalid'), $errors['tags']);
    }

    public function testASelectWhoseValuesAreNumbersAcceptsThem(): void
    {
        $list = new Field('size', 'select', 'Size', '', null, ['options' => [1, 2, 3]]);
        $map  = new Field('size', 'select', 'Size', '', null, ['multiple' => true, 'options' => [10 => 'Ten', 20 => 'Twenty']]);

        $this->assertSame([], $this->validateField($list, '2'));
        $this->assertArrayHasKey('size', $this->validateField($list, '4'));
        $this->assertSame([], $this->validateField($map, ['10', '20']));
        $this->assertArrayHasKey('size', $this->validateField($map, ['10', '30']));
    }

    public function testASelectCanBeRequired(): void
    {
        $field  = new Field('mood', 'select', 'Mood', 'required', null, ['options' => ['a']]);
        $errors = $this->validateField($field, '');

        $this->assertArrayHasKey('mood', $errors);
        $this->assertSame([], $this->validateField($field, 'a'));
    }

    // integer

    public function testAnIntegerIsKeptAsANumber(): void
    {
        $form   = $this->form();
        $values = $form->prepare(['fields' => ['title' => 'T', 'rating' => ' +4 ']]);

        $this->assertSame('4', $values['rating']);
        $this->assertSame([], $form->validate($values));
        $this->assertSame(4, $form->apply($values, [], '')[0]['rating']);
        $this->assertSame('4', $form->valuesOf(['rating' => 4], '', 'x')['rating']);
        $this->assertSame('4', $form->valuesOf(['rating' => 4.0], '', 'x')['rating']);
        $this->assertSame('', $form->valuesOf(['rating' => 'many'], '', 'x')['rating']);
        $this->assertSame('', $form->valuesOf(['rating' => 4.5], '', 'x')['rating']);
    }

    public function testAnIntegerRefusesWhatIsNotAWholeNumberInRange(): void
    {
        $form = $this->form();

        $this->assertSame(lang('Content.integerInvalid'), $form->validate($form->prepare(['fields' => ['title' => 'T', 'rating' => '2.5']]))['rating']);
        $this->assertSame(lang('Content.integerInvalid'), $form->validate($form->prepare(['fields' => ['title' => 'T', 'rating' => 'three']]))['rating']);
        $this->assertSame(lang('Content.integerInvalid'), $form->validate($form->prepare(['fields' => ['title' => 'T', 'rating' => '99999999999999999999']]))['rating']);
        $this->assertSame(lang('Content.integerMin', [1]), $form->validate($form->prepare(['fields' => ['title' => 'T', 'rating' => '0']]))['rating']);
        $this->assertSame(lang('Content.integerMax', [5]), $form->validate($form->prepare(['fields' => ['title' => 'T', 'rating' => '6']]))['rating']);
        $this->assertSame([], $form->validate($form->prepare(['fields' => ['title' => 'T', 'rating' => '5']])));
    }

    public function testAClosureRuleKeepsAPipeInsideBracketsInTheBlueprintsRules(): void
    {
        $rules = (new IntegerField())->rules(new Field('n', 'integer', 'N', 'required|regex_match[/^(1|2)$/]|max_length[3]', null, []));

        $this->assertIsArray($rules);
        $this->assertSame(['required', 'regex_match[/^(1|2)$/]', 'max_length[3]', 'permit_empty'], array_slice($rules, 0, 4));
    }

    public function testAnIntegerLeftEmptyAddsNoKeyAndClearsOneThatIsThere(): void
    {
        $form   = $this->form();
        $values = $form->prepare(['fields' => ['title' => 'T', 'rating' => '']]);

        $this->assertSame([], $form->validate($values));
        $this->assertArrayNotHasKey('rating', $form->apply($values, [], '')[0]);
        $this->assertNull($form->apply($values, ['rating' => 3], '')[0]['rating']);
    }

    // datetime

    public function testADatetimeIsKeptWithItsTime(): void
    {
        $form   = $this->form();
        $values = $form->prepare(['fields' => ['title' => 'T', 'starts_at' => '2026-12-24T18:30']]);

        $this->assertSame('2026-12-24 18:30:00', $values['starts_at']);
        $this->assertSame([], $form->validate($values));
        $this->assertSame('2026-12-24 18:30:00', $form->apply($values, [], '')[0]['starts_at']);
        $this->assertSame('2026-12-24 18:30:00', $form->valuesOf(['starts_at' => '2026-12-24 18:30:00'], '', 'x')['starts_at']);
        $this->assertSame('', $form->valuesOf([], '', 'x')['starts_at']);
    }

    public function testADatetimeRefusesWhatIsNotADateAndTime(): void
    {
        $form = $this->form();

        foreach (['next week', '2026-02-31T10:00'] as $posted) {
            $errors = $form->validate($form->prepare(['fields' => ['title' => 'T', 'starts_at' => $posted]]));

            $this->assertArrayHasKey('starts_at', $errors, $posted);
        }
    }

    // entries

    public function testEntriesOffersOnlyEntriesOfTheAllowedCollections(): void
    {
        $this->trashOld();

        $related = $this->blueprint()->field('related');
        $offered = array_map(static fn (Entry $entry): string => $entry->id, EntriesField::choices($related));

        $this->assertSame([self::ABOUT, self::CONTACT], $offered, 'the trashed page and the blog posts are not offered');

        $anything = array_map(static fn (Entry $entry): string => $entry->collection, EntriesField::choices($this->blueprint()->field('anything')));
        $this->assertSame(['blog', 'pages'], array_values(array_unique($anything)));
    }

    public function testEntriesTakesASingleCollectionNamedWithoutAList(): void
    {
        $field = new Field('r', 'entries', 'R', '', null, ['collections' => 'pages']);

        $this->assertSame(['pages'], EntriesField::collections($field));
        $this->assertSame([self::ABOUT, self::CONTACT, self::OLD], array_map(static fn (Entry $entry): string => $entry->id, EntriesField::choices($field)));
    }

    public function testEntriesKeepsTheIdsAsAListInTheFrontMatter(): void
    {
        $form   = $this->form();
        $values = $form->prepare(['fields' => ['title' => 'T', 'related' => [strtoupper(self::CONTACT), self::ABOUT, self::ABOUT]]]);

        $this->assertSame([], $form->validate($values));
        [$frontMatter] = $form->apply($values, [], '');
        $this->assertSame([self::CONTACT, self::ABOUT], $frontMatter['related']);
        $this->assertSame($values['related'], $form->valuesOf($frontMatter, '', 'x')['related']);
    }

    public function testEntriesRefusesWhatIsNotAnAllowedLiveEntryOrIsTooMany(): void
    {
        $this->trashOld();

        $form = $this->form();

        $cases = [
            'an entry of another collection' => [[self::FIRST], 'entriesInvalid'],
            'an entry that does not exist'   => [['99999999-9999-4999-8999-999999999999'], 'entriesInvalid'],
            'a good id with a bad one'       => [[self::ABOUT, 'nope'], 'entriesInvalid'],
            'more than max_items'            => [[self::ABOUT, self::CONTACT, self::OLD], 'entriesTooMany'],
        ];

        foreach ($cases as $name => [$posted, $message]) {
            $errors = $form->validate($form->prepare(['fields' => ['title' => 'T', 'related' => $posted]]));

            $this->assertArrayHasKey('related', $errors, $name);
            $this->assertStringContainsString($message === 'entriesTooMany' ? lang('Content.entriesTooMany', [2]) : lang('Content.entriesInvalid'), $errors['related'], $name);
        }

        $this->assertArrayHasKey('see_also', $form->validate($form->prepare(['fields' => ['title' => 'T', 'see_also' => [self::ABOUT, self::CONTACT]]])));
        $this->assertSame([], $form->validate($form->prepare(['fields' => ['title' => 'T', 'see_also' => [self::FIRST]]])), 'no collections means any');
    }

    public function testEntriesResolveToEntriesAndSkipMissingAndTrashedTargets(): void
    {
        $missing = '99999999-9999-4999-8999-999999999999';
        $this->writeFile('collections/blog/2026-05-05.links.md', "---\nid: 77777777-7777-4777-8777-777777777777\ntitle: Links\nrelated:\n  - " . self::CONTACT . "\n  - {$missing}\n  - " . self::OLD . "\n  - " . self::ABOUT . "\nbroken: nope\n---\nBody.\n");
        (new Indexer($this->site))->reindex();
        $this->trashOld();

        $entry = content()->find('77777777-7777-4777-8777-777777777777');

        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertSame([self::CONTACT, self::ABOUT], array_map(static fn (Entry $linked): string => $linked->id, $entry->entries('related')));
        $this->assertSame('Contact page', $entry->entries('related')[0]->title);
        $this->assertSame([], $entry->entries('broken'));
        $this->assertSame([], $entry->entries('absent'));
        $this->assertSame([], content()->findMany([]));
    }

    // Through the admin form

    public function testEveryNewTypeRendersAndRoundTripsThroughTheAdminForm(): void
    {
        $this->trashOld();

        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));
        $response->assertOK();

        $this->assertCount(1, $this->xpath($response, '//select[@name="fields[mood]"]'));
        $this->assertCount(3, $this->xpath($response, '//select[@name="fields[tags][]"][@multiple]/option'));
        $this->assertCount(1, $this->xpath($response, '//input[@type="number"][@name="fields[rating]"][@min="1"][@max="5"]'));
        $this->assertCount(1, $this->xpath($response, '//input[@type="datetime-local"][@name="fields[starts_at]"]'));
        $this->assertCount(2, $this->xpath($response, '//input[@type="checkbox"][@name="fields[related][]"]'), 'only the pages');
        $this->assertCount(1, $this->xpath($response, '//select[@name="fields[see_also][]"]'));
        $this->assertGreaterThan(2, count($this->xpath($response, '//input[@type="checkbox"][@name="fields[anything][]"]')));

        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint' => 'post',
            'fields'    => [
                'title'     => 'Types',
                'mood'      => 'happy',
                'genre'     => 'sf',
                'tags'      => ['php', 'css'],
                'rating'    => '4',
                'starts_at' => '2026-12-24T18:30',
                'related'   => [self::ABOUT, self::CONTACT],
            ],
        ])->assertRedirect();

        $row  = (new ContentEntryModel())->where('slug', 'types')->first();
        $data = Entry::fromRow($row)->data;

        $this->assertSame('happy', $data['mood']);
        $this->assertSame('sf', $data['genre']);
        $this->assertSame(['php', 'css'], $data['tags']);
        $this->assertSame(4, $data['rating']);
        $this->assertSame('2026-12-24 18:30:00', $data['starts_at']);
        $this->assertSame([self::ABOUT, self::CONTACT], $data['related']);
        $this->assertArrayNotHasKey('see_also', $data);

        // The edit form shows what the file holds.
        $edit = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', $row['id']));

        $this->assertSame('happy', $this->xpath($edit, '//select[@name="fields[mood]"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('sf', $this->xpath($edit, '//select[@name="fields[genre]"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame(['php', 'css'], $this->values($this->xpath($edit, '//select[@name="fields[tags][]"]/option[@selected]')));
        $this->assertSame('4', $this->xpath($edit, '//input[@name="fields[rating]"]')->item(0)->getAttribute('value'));
        $this->assertSame('2026-12-24T18:30:00', $this->xpath($edit, '//input[@name="fields[starts_at]"]')->item(0)->getAttribute('value'));
        $this->assertSame([self::ABOUT, self::CONTACT], $this->values($this->xpath($edit, '//input[@name="fields[related][]"][@checked]')));

        // Saving the form as it is changes nothing.
        $fields                   = $this->formFields($edit->getBody(), url_to('content-edit', 'blog', $row['id']));
        $fields['fields']['tags'] = ['php', 'css'];

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', $row['id']), $fields)->assertRedirect();

        $this->assertSame($data, Entry::fromRow((new ContentEntryModel())->find($row['id']))->data);
    }

    public function testABadValueRerendersTheFormWithTheErrorAndWritesNothing(): void
    {
        $before = glob($this->site . 'collections/blog/*');

        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint' => 'post',
            'fields'    => ['title' => 'Bad', 'mood' => 'angry', 'rating' => '9', 'related' => [self::FIRST], 'starts_at' => 'soon'],
        ]);

        $response->assertOK();

        foreach (['mood', 'rating', 'related', 'starts_at'] as $handle) {
            $this->assertCount(1, $this->xpath($response, "//*[@data-error=\"{$handle}\"]"), $handle);
        }

        $this->assertSame($before, glob($this->site . 'collections/blog/*'));
    }

    public function testATrashedLinkStaysOnSave(): void
    {
        $this->writeFile('collections/blog/2026-05-05.links.md', "---\nid: 77777777-7777-4777-8777-777777777777\ntitle: Links\nrelated:\n  - " . self::OLD . "\n  - " . self::ABOUT . "\n---\nBody.\n");
        (new Indexer($this->site))->reindex();
        $this->trashOld();

        $edit   = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', '77777777-7777-4777-8777-777777777777'));
        $fields = $this->formFields($edit->getBody(), url_to('content-edit', 'blog', '77777777-7777-4777-8777-777777777777'));

        $this->assertEqualsCanonicalizing([self::OLD, self::ABOUT], $this->values($this->xpath($edit, '//input[@name="fields[related][]"][@checked]')));

        $this->actingAs($this->admin)->post(route_to('content-edit', 'blog', '77777777-7777-4777-8777-777777777777'), $fields)->assertRedirect();

        $this->assertEqualsCanonicalizing([self::OLD, self::ABOUT], Entry::fromRow((new ContentEntryModel())->find('77777777-7777-4777-8777-777777777777'))->data['related']);
    }

    // Field types of an app's own

    public function testAFieldTypeRegisteredInConfigAppearsInBlueprintDrivenForms(): void
    {
        $this->registerShout();
        $this->useFields("  - handle: loud\n    type: shout\n");

        $this->assertContains('shout', FieldTypes::names());
        $this->assertInstanceOf(ShoutField::class, FieldTypes::get('shout'));

        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));
        $response->assertOK();
        $this->assertCount(1, $this->xpath($response, '//input[@type="text"][@name="fields[loud]"]'));

        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['blueprint' => 'post', 'fields' => ['title' => 'Loud', 'loud' => 'hey']])->assertRedirect();
        $this->assertSame('HEY', Entry::fromRow((new ContentEntryModel())->where('slug', 'loud')->first())->data['loud']);

        $tooLong = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), ['blueprint' => 'post', 'fields' => ['title' => 'Louder', 'loud' => 'far too long for this']]);
        $this->assertCount(1, $this->xpath($tooLong, '//*[@data-error="loud"]'));
    }

    public function testABlueprintCannotUseAFieldTypeThatIsNotRegistered(): void
    {
        $this->useFields("  - handle: loud\n    type: shout\n");

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('needs a `type`');

        $this->blueprint();
    }

    public function testARegisteredClassThatIsNotAFieldTypeIsRefused(): void
    {
        $config             = config('Content');
        $config->fieldTypes = ['shout' => self::class, 'ghost' => 'No\Such\Class'];

        $this->assertFalse(FieldTypes::has('shout'));
        $this->assertFalse(FieldTypes::has('ghost'));
        $this->assertSame(['text', 'textarea', 'markdown', 'slug', 'toggle', 'date', 'datetime', 'select', 'integer', 'entries', 'terms', 'blocks'], FieldTypes::names());
    }

    /**
     * Moves the page "old" to the trash. Do it after the last reindex, which would meet its row.
     */
    private function trashOld(): void
    {
        (new ContentEntryModel())->delete(self::OLD);
    }

    private function registerShout(): void
    {
        config('Content')->fieldTypes = ['shout' => ShoutField::class];
    }

    private function useFields(string $fields): void
    {
        file_put_contents(
            $this->blueprints . 'collections/blog/post.yaml',
            "title: Post\nfields:\n  - handle: title\n    type: text\n    validate: required\n  - handle: slug\n    type: slug\n" . $fields . "\n",
        );
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
     * @return array<string, string> the errors by handle, for one field posted with this value
     */
    private function validateField(Field $field, array|string $posted): array
    {
        $type   = FieldTypes::get($field->type);
        $values = [$field->handle => $type->prepare($posted, $field, [])];
        $form   = new EntryForm(new Blueprint('blog', 'x', 'X', [$field]), true);

        return $form->validate($values);
    }

    /**
     * @return DOMNodeList<DOMElement>
     */
    private function xpath(TestResponse $response, string $query): DOMNodeList
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $response->getBody());
        libxml_clear_errors();

        return (new DOMXPath($dom))->query($query);
    }

    /**
     * @param DOMNodeList<DOMElement> $elements
     *
     * @return list<string>
     */
    private function values(DOMNodeList $elements): array
    {
        $values = [];

        foreach ($elements as $element) {
            $values[] = $element->getAttribute('value');
        }

        return $values;
    }
}
