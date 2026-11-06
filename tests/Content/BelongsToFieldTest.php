<?php

namespace Tests\Content;

use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\EntryForm;
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Fields\FieldException;
use Bonfire\Fields\OptionsToken;
use Bonfire\Fields\Types\BelongsToField;
use Bonfire\Users\Models\UserModel;
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
 * The belongsTo field type: a select of another model's records, or a typeahead above `searchAbove` records,
 * which stores the chosen ID.
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
        config('Encryption')->key = 'a-key-for-the-tests';

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

    public function testAboveSearchAboveItRendersATypeaheadInsteadOfASelect(): void
    {
        $this->useSearchable(2);

        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));
        $response->assertOK();

        $this->assertCount(0, $this->xpath($response, '//select[@name="fields[author]"]'));
        $this->assertCount(1, $this->xpath($response, '//input[@name="fields[author]"]'), 'the input that holds the ID');

        $search = $this->xpath($response, '//input[@hx-get]');

        $this->assertCount(1, $search);
        $this->assertSame(route_to('fields-options'), $search->item(0)->getAttribute('hx-get'));
        $this->assertSame(
            ['model' => UserModel::class, 'title' => 'username', 'search' => ['username'], 'permission' => null],
            OptionsToken::read($this->tokenOf($response)),
        );
    }

    public function testUpToSearchAboveItStaysASelect(): void
    {
        $this->useSearchable(3);

        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));

        $this->assertCount(1, $this->xpath($response, '//select[@name="fields[author]"]'), 'three records are not above three');
        $this->assertCount(0, $this->xpath($response, '//input[@hx-get]'));
    }

    public function testTheTypeaheadSavesTheChosenIdAndTheEditFormShowsTheTitle(): void
    {
        $this->useSearchable(2);

        $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint' => 'post',
            'fields'    => ['title' => 'Written', 'author' => (string) $this->ada->id],
        ])->assertRedirect();

        $row = (new ContentEntryModel())->where('slug', 'written')->first();
        $this->assertSame($this->ada->id, Entry::fromRow($row)->data['author']);

        $edit = $this->actingAs($this->admin)->get(route_to('content-edit', 'blog', $row['id']));

        $this->assertSame([(string) $this->ada->id], $this->values($this->xpath($edit, '//input[@name="fields[author]"]')));
        $this->assertSame(['ada'], $this->values($this->xpath($edit, '//input[@hx-get]')), 'the title, not the ID');
    }

    public function testWithoutJavaScriptTheTypeaheadTakesAnExistingIdAndRejectsAnUnknownOne(): void
    {
        $this->useSearchable(2);

        $form = $this->form();

        $this->assertSame([], $form->validate($form->prepare(['fields' => ['title' => 'T', 'author' => (string) $this->grace->id]])));
        $this->assertSame(
            lang('Fields.belongsToInvalid'),
            $form->validate($form->prepare(['fields' => ['title' => 'T', 'author' => (string) ($this->grace->id + 100)]]))['author'] ?? null,
        );

        $response = $this->actingAs($this->admin)->post(route_to('content-new', 'blog'), [
            'blueprint' => 'post',
            'fields'    => ['title' => 'Bad', 'author' => '9999'],
        ]);

        $response->assertOK();
        $this->assertCount(1, $this->xpath($response, '//*[@data-error="author"]'));
        $this->assertSame(['9999'], $this->values($this->xpath($response, '//input[@name="fields[author]"]')), 'what was typed stays in the input');
    }

    public function testTheEndpointGivesTheTitlesThatMatchTheQuery(): void
    {
        $response = $this->fetchOptions($this->token(), 'gr');

        $response->assertOK();
        $this->assertSame([(string) $this->grace->id], $this->optionIds($response));
        $this->assertSame('grace', $this->xpath($response, '//*[@data-id]')->item(0)->textContent);

        $this->assertSame(
            [(string) $this->ada->id, (string) $this->admin->id, (string) $this->grace->id],
            $this->optionIds($this->fetchOptions($this->token(), 'a')),
            'in title order',
        );
        $this->assertSame([], $this->optionIds($this->fetchOptions($this->token(), 'nobody')));
    }

    public function testTheEndpointRefusesAMissingTamperedOrUnsignedToken(): void
    {
        $token            = $this->token();
        [$payload, $sign] = explode('.', $token);
        $other            = OptionsToken::create(UserModel::class, 'first_name', ['first_name'], null);

        $this->actingAs($this->admin)->get(route_to('fields-options'))->assertStatus(403);
        $this->actingAs($this->admin)->get(route_to('fields-options') . '?q=a')->assertStatus(403);

        $refused = [
            'empty'                  => '',
            'not a token'            => 'abc',
            'no signature'           => $payload,
            'an empty signature'     => $payload . '.',
            'a wrong signature'      => $payload . '.' . str_repeat('0', strlen($sign)),
            'another payload'        => explode('.', $other)[0] . '.' . $sign,
            'a payload with no sign' => base64_encode((string) json_encode(['model' => UserModel::class, 'title' => 'first_name', 'search' => ['first_name'], 'permission' => null])),
            'no payload'             => '.' . $sign,
            'extra parts'            => $token . '.x',
            'signed by another key'  => $this->signedWith('another key'),
        ];

        foreach ($refused as $name => $value) {
            $response = $this->actingAs($this->admin)->get(route_to('fields-options') . '?' . http_build_query(['field' => $value, 'q' => 'a']));

            $response->assertStatus(403);
            $this->assertStringNotContainsString('data-id', $response->getBody(), $name);
        }

        $this->assertNull(OptionsToken::read(['field' => $token]), 'an array is not a token');
    }

    public function testTheEndpointNeedsAdminAccess(): void
    {
        $guest = $this->createUser(['username' => 'guest']);
        $query = '?' . http_build_query(['field' => $this->token(), 'q' => 'a']);

        $response = $this->actingAs($guest)->get(route_to('fields-options') . $query);

        $this->assertNotSame(200, $response->response()->getStatusCode());
        $this->assertStringNotContainsString('data-id', $response->getBody());
    }

    public function testTheEndpointNeedsTheFieldsPermissionWhenItNamesOne(): void
    {
        $token = $this->token('users.manage-admins');

        $this->fetchOptions($token, 'a')->assertOK();

        $manager = $this->createUser(['username' => 'manager']);
        $manager->addGroup('admin');

        $response = $this->actingAs($manager)->get(route_to('fields-options') . '?' . http_build_query(['field' => $token, 'q' => 'a']));

        $response->assertStatus(403);
        $this->assertStringNotContainsString('data-id', $response->getBody());

        $this->actingAs($manager)->get(route_to('fields-options') . '?' . http_build_query(['field' => $this->token(), 'q' => 'a']))->assertOK();
    }

    public function testTheTypeaheadsTokenCarriesTheFieldsPermission(): void
    {
        $this->useSearchable(2, "\n    permission: users.manage-admins");

        $response = $this->actingAs($this->admin)->get(route_to('content-new', 'blog'));

        $this->assertSame('users.manage-admins', OptionsToken::read($this->tokenOf($response))['permission'] ?? null);
    }

    public function testTheEndpointCapsItsResults(): void
    {
        for ($i = 0; $i < BelongsToField::SEARCH_LIMIT + 5; $i++) {
            $this->createUser(['username' => sprintf('writer%02d', $i)]);
        }

        $this->assertCount(BelongsToField::SEARCH_LIMIT, $this->optionIds($this->fetchOptions($this->token(), 'writer')));
        $this->assertCount(BelongsToField::SEARCH_LIMIT, $this->optionIds($this->fetchOptions($this->token(), '')), 'with no query too');
    }

    public function testTheEndpointOnlySearchesTheTokensColumnsAndTakesTheQueryLiterally(): void
    {
        $this->createUser(['username' => 'plain', 'first_name' => 'Zelda']);
        $sure = $this->createUser(['username' => '100%_sure']);

        $this->assertSame([], $this->optionIds($this->fetchOptions($this->token(), 'Zelda')), 'first_name is not in the token');
        $this->assertSame([(string) $sure->id], $this->optionIds($this->fetchOptions($this->token(), '%')), 'a % is not a wildcard');
        $this->assertSame([(string) $sure->id], $this->optionIds($this->fetchOptions($this->token(), '0%_s')), 'but it matches itself');
        $this->assertSame([], $this->optionIds($this->fetchOptions($this->token(), 'p_ain')), 'a _ is not a wildcard');

        $quoted = $this->createUser(['username' => "o'brien!"]);

        $this->assertSame([(string) $quoted->id], $this->optionIds($this->fetchOptions($this->token(), "o'brien!")), 'a quote and the escape character are text too');
        $this->assertCount(1, $this->optionIds($this->fetchOptions(OptionsToken::create(UserModel::class, 'username', ['first_name'], null), 'Zelda')), 'a column the token names is searched');
    }

    public function testWithoutAnEncryptionKeyThereIsNoTokenToMakeOrTrust(): void
    {
        $token = $this->token();

        config('Encryption')->key = '';

        $this->assertNull(OptionsToken::read($token));

        $this->expectException(FieldException::class);
        $this->expectExceptionMessage('encryption key');

        $this->token();
    }

    /**
     * Makes the blog's author field a belongsTo over the users.
     */
    private function useSearchable(int $searchAbove, string $more = ''): void
    {
        $this->useFields(self::FIELD . "\n    searchAbove: {$searchAbove}" . $more);
    }

    /**
     * A token for the users' usernames, as the field makes it.
     */
    private function token(?string $permission = null): string
    {
        return OptionsToken::create(UserModel::class, 'username', ['username'], $permission);
    }

    private function signedWith(string $key): string
    {
        $payload = rtrim(strtr(base64_encode((string) json_encode(['model' => UserModel::class, 'title' => 'first_name', 'search' => ['first_name'], 'permission' => null])), '+/', '-_'), '=');

        return $payload . '.' . hash_hmac('sha256', 'fields-options|' . $payload, $key);
    }

    private function fetchOptions(string $token, string $query): TestResponse
    {
        return $this->actingAs($this->admin)->get(route_to('fields-options') . '?' . http_build_query(['field' => $token, 'q' => $query]));
    }

    private function tokenOf(TestResponse $response): string
    {
        $vals = json_decode($this->xpath($response, '//input[@hx-get]')->item(0)->getAttribute('hx-vals'), true);

        return $vals['field'];
    }

    /**
     * @return list<string>
     */
    private function optionIds(TestResponse $response): array
    {
        $ids = [];

        foreach ($this->xpath($response, '//*[@data-id]') as $option) {
            $ids[] = $option->getAttribute('data-id');
        }

        return $ids;
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
