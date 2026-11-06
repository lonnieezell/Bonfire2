<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\EntryForm;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Users\User;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Content\ContentTestCase;

/**
 * The belongsTo field type: a select of another model's records, which stores the chosen ID.
 *
 * @internal
 */
final class BelongsToFieldTest extends ContentTestCase
{
    private const FIELD = <<<'YAML'
          - handle: author
            type: belongsTo
            model: Bonfire\Users\Models\UserModel
            title: username
        YAML;

    private User $admin;
    private User $ada;
    private User $grace;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        // route_to() runs before the first request, and another test class may have left the routes unloaded.
        service('routes')->loadRoutes();

        $this->useFields(self::FIELD);

        $this->admin = $this->createUser(['username' => 'admin']);
        $this->admin->addGroup('superadmin');
        $this->ada   = $this->createUser(['username' => 'ada']);
        $this->grace = $this->createUser(['username' => 'grace']);
    }

    protected function tearDown(): void
    {
        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        parent::tearDown();
    }

    public function testItRendersASelectOfTheModelsRecordsAndStoresTheChosenId(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));
        $response->assertOK();

        $options = $this->xpath($response, '//select[@name="fields[author]"]/option');

        $this->assertSame(['', (string) $this->ada->id, (string) $this->admin->id, (string) $this->grace->id], $this->values($options), 'an empty choice, then a record each in title order');
        $this->assertSame('ada', $options->item(1)->textContent);

        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint' => 'post',
            'fields'    => ['title' => 'Written', 'author' => (string) $this->ada->id],
        ])->assertRedirect();

        $row = (new ContentEntryModel())->where('slug', 'written')->first();
        $this->assertSame($this->ada->id, Entry::fromRow($row)->data['author']);

        $edit = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', $row['id']));

        $this->assertSame([(string) $this->ada->id], $this->values($this->xpath($edit, '//select[@name="fields[author]"]/option[@selected]')));
    }

    public function testReadingTheEntryGivesTheIdNotTheRecord(): void
    {
        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint' => 'post',
            'fields'    => ['title' => 'Written', 'author' => (string) $this->grace->id],
        ])->assertRedirect();

        $entry = Entry::fromRow((new ContentEntryModel())->where('slug', 'written')->first());

        $this->assertSame($this->grace->id, $entry->field('author'));
    }

    public function testAnEmptyChoiceAddsNoKey(): void
    {
        $form   = $this->form();
        $values = $form->prepare(['fields' => ['title' => 'T', 'author' => '']]);

        $this->assertSame([], $form->validate($values));
        $this->assertArrayNotHasKey('author', $form->apply($values, [], '')[0]);
    }

    public function testAPostedIdThatIsNotARecordOfTheModelIsRejected(): void
    {
        $form = $this->form();

        foreach ([(string) ($this->grace->id + 100), 'nobody', '1; DROP TABLE users'] as $posted) {
            $errors = $form->validate($form->prepare(['fields' => ['title' => 'T', 'author' => $posted]]));

            $this->assertSame(lang('Fields.belongsToInvalid'), $errors['author'] ?? null, $posted);
        }

        $this->assertSame([], $form->validate($form->prepare(['fields' => ['title' => 'T', 'author' => (string) $this->ada->id]])));

        $before   = glob($this->site . 'collections/blog/*');
        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint' => 'post',
            'fields'    => ['title' => 'Bad', 'author' => 'nobody'],
        ]);

        $response->assertOK();
        $this->assertCount(1, $this->xpath($response, '//*[@data-error="author"]'));
        $this->assertSame($before, glob($this->site . 'collections/blog/*'), 'nothing was written');
    }

    #[DataProvider('provideABlueprintThatCannotFindItsRecordsFailsItsCheck')]
    public function testABlueprintThatCannotFindItsRecordsFailsItsCheck(string $fields, string $message): void
    {
        $this->useFields($fields);

        $this->expectException(ContentException::class);
        $this->expectExceptionMessage($message);

        $this->blueprint();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideABlueprintThatCannotFindItsRecordsFailsItsCheck(): iterable
    {
        yield 'no model' => ["  - handle: author\n    type: belongsTo\n    title: username\n", 'needs a `model`'];

        yield 'a model class that does not exist' => ["  - handle: author\n    type: belongsTo\n    model: App\\Models\\GhostModel\n    title: username\n", 'needs a `model`'];

        yield 'a class that is not a model' => ["  - handle: author\n    type: belongsTo\n    model: stdClass\n    title: username\n", 'needs a `model`'];

        yield 'no title' => ["  - handle: author\n    type: belongsTo\n    model: Bonfire\\Users\\Models\\UserModel\n", 'needs a `title`'];

        yield 'a title column that does not exist' => ["  - handle: author\n    type: belongsTo\n    model: Bonfire\\Users\\Models\\UserModel\n    title: nickname\n", 'has no `nickname` column'];
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
