<?php

namespace Tests\Content;

use Bonfire\Users\User;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Tests\Support\Content\ContentTestCase;
use Tests\Support\SubmitsForms;

/**
 * Editing globals in the admin: a form from the set's blueprint, saved to its YAML file.
 *
 * @internal
 */
final class GlobalAdminTest extends ContentTestCase
{
    use SubmitsForms;

    private User $admin;
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        foreach (['auth', 'bonfire', 'menus', 'widgets'] as $service) {
            Services::resetSingle($service);
        }

        // FrontEndRoutingTest leaves the routes reset and not loaded, and route_to() before a request then finds none of them.
        service('routes')->loadRoutes();

        mkdir($this->site . 'globals');
        mkdir($this->blueprints . 'globals');

        $this->file = $this->site . 'globals/site.yaml';

        $this->writeFile('globals/site.yaml', <<<'YAML'
            # The site's own values.
            name: Bonfire
            posts_per_page: "12"
            maintenance: true
            launched: 2026-10-27
            analytics_id: UA-1
            YAML);
        file_put_contents($this->blueprints . 'globals/site.yaml', <<<'YAML'
            title: Site settings
            fields:
              - handle: name
                type: text
                validate: required|max_length[20]
              - handle: tagline
                type: text
              - handle: posts_per_page
                type: integer
                min: 1
              - handle: maintenance
                type: toggle
              - handle: launched
                type: date
            YAML);

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

    // The menu and the permissions

    public function testTheMenuHasAnItemForEachGlobalThatHasABlueprint(): void
    {
        $this->writeFile('globals/footer.yaml', "text: Hello\n");

        $response = $this->actingAs($this->admin)->get(ADMIN_AREA);

        $response->assertOK();
        $this->assertStringContainsString(route_to('content-global', 'site'), $response->getBody());
        // Nothing describes the footer, so there's no form to build.
        $this->assertStringNotContainsString(route_to('content-global', 'footer'), $response->getBody());
    }

    public function testTheMenuShowsGlobalsEvenWithoutCollections(): void
    {
        foreach (glob($this->site . 'collections/*.yaml') as $config) {
            unlink($config);
        }

        $response = $this->actingAs($this->admin)->get(ADMIN_AREA);

        $this->assertStringContainsString(route_to('content-global', 'site'), $response->getBody());
    }

    public function testAUserWhoCanEditContentSeesTheItemAndOpensTheForm(): void
    {
        $user = $this->userWith('content.access', 'content.edit');

        $this->assertStringContainsString(route_to('content-global', 'site'), $this->actingAs($user)->get(ADMIN_AREA)->getBody());
        $this->actingAs($user)->get(route_to('content-global', 'site'))->assertOK();
    }

    public function testAUserWithoutEditHasNoItemAndCannotOpenOrSaveTheForm(): void
    {
        $user   = $this->userWith('content.access');
        $before = file_get_contents($this->file);

        $this->assertStringNotContainsString(route_to('content-global', 'site'), $this->actingAs($user)->get(ADMIN_AREA)->getBody());

        $this->actingAs($user)->get(route_to('content-global', 'site'))->assertRedirectTo(site_url(ADMIN_AREA));

        $response = $this->actingAs($user)->post(route_to('content-global', 'site'), ['hash' => hash('sha256', $before), 'fields' => ['name' => 'Hacked']]);

        $response->assertRedirectTo(site_url(ADMIN_AREA));
        $response->assertSessionHas('error', lang('Bonfire.notAuthorized'));
        $this->assertSame($before, file_get_contents($this->file));
    }

    public function testAUserWithoutContentAccessCannotOpenOrSaveTheForm(): void
    {
        $user   = $this->userWith('content.edit');
        $before = file_get_contents($this->file);

        $this->assertStringNotContainsString(route_to('content-global', 'site'), $this->actingAs($user)->get(ADMIN_AREA)->getBody());

        $this->actingAs($user)->get(route_to('content-global', 'site'))->assertRedirectTo(site_url(ADMIN_AREA));
        $this->actingAs($user)->post(route_to('content-global', 'site'), ['hash' => hash('sha256', $before), 'fields' => ['name' => 'Hacked']])->assertRedirectTo(site_url(ADMIN_AREA));
        $this->assertSame($before, file_get_contents($this->file));
    }

    // The form

    public function testTheFormIsBuiltFromTheBlueprintAndShowsTheFilesValues(): void
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-global', 'site'));

        $response->assertOK();
        $this->assertCount(5, $this->query($response, '//*[@data-field]'));
        $this->assertSame('Bonfire', $this->query($response, '//input[@name="fields[name]"]')->item(0)->getAttribute('value'));
        $this->assertSame('', $this->query($response, '//input[@name="fields[tagline]"]')->item(0)->getAttribute('value'));
        $this->assertSame('12', $this->query($response, '//input[@name="fields[posts_per_page]"]')->item(0)->getAttribute('value'));
        $this->assertTrue($this->query($response, '//input[@name="fields[maintenance]"][@type="checkbox"]')->item(0)->hasAttribute('checked'));
        $this->assertSame('2026-10-27', $this->query($response, '//input[@name="fields[launched]"]')->item(0)->getAttribute('value'));
        $this->assertSame(hash('sha256', (string) file_get_contents($this->file)), $this->query($response, '//input[@name="hash"]')->item(0)->getAttribute('value'));
    }

    public function testTheFormShowsTheFileNotAnEarlierRead(): void
    {
        $this->writeFile('globals/site.yaml', "name: Edited by hand\n");

        $response = $this->actingAs($this->admin)->get(route_to('content-global', 'site'));

        $this->assertSame('Edited by hand', $this->query($response, '//input[@name="fields[name]"]')->item(0)->getAttribute('value'));
    }

    public function testAnUnknownGlobalIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('content-global', 'nope'));
    }

    public function testSavingAnUnknownGlobalIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->post(route_to('content-global', 'nope'), ['hash' => '', 'fields' => ['name' => 'x']]);
    }

    public function testAGlobalWithoutABlueprintHasNoForm(): void
    {
        $this->writeFile('globals/footer.yaml', "text: Hello\n");
        $this->expectException(PageNotFoundException::class);

        $this->actingAs($this->admin)->get(route_to('content-global', 'footer'));
    }

    public function testSavingAGlobalWithoutABlueprintWritesNothing(): void
    {
        $this->writeFile('globals/footer.yaml', "text: Hello\n");

        try {
            $this->actingAs($this->admin)->post(route_to('content-global', 'footer'), ['hash' => hash('sha256', "text: Hello\n"), 'fields' => ['text' => 'x']]);
            $this->fail('The form was served.');
        } catch (PageNotFoundException) {
            $this->assertSame("text: Hello\n", file_get_contents($this->site . 'globals/footer.yaml'));
        }
    }

    // Saving

    public function testASaveWritesTheFileAndGlobalsShowsTheNewValues(): void
    {
        $fields = $this->openForm();

        $fields['fields']['name']           = 'New name';
        $fields['fields']['tagline']        = 'A new tagline';
        $fields['fields']['posts_per_page'] = '25';
        unset($fields['fields']['maintenance']);
        $fields['fields']['launched'] = '2026-11-05';

        $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields)->assertRedirectTo(site_url(route_to('content-global', 'site')));

        $site = globals('site');
        $this->assertSame('New name', $site->field('name'));
        $this->assertSame('A new tagline', $site->field('tagline'));
        $this->assertSame(25, $site->field('posts_per_page'));
        $this->assertFalse($site->field('maintenance'));
        $this->assertSame('2026-11-05', $site->field('launched'));
    }

    public function testASaveShowsAMessageAndTheFormAgainWithTheNewHash(): void
    {
        $fields = $this->openForm();

        $fields['fields']['name'] = 'Renamed';

        $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields)->assertSessionHas('message');

        $again = $this->actingAs($this->admin)->get(route_to('content-global', 'site'));

        $this->assertSame(hash('sha256', (string) file_get_contents($this->file)), $this->query($again, '//input[@name="hash"]')->item(0)->getAttribute('value'));
    }

    public function testValuesTheBlueprintDoesNotKnowStayInTheFile(): void
    {
        $fields = $this->openForm();

        $fields['fields']['name'] = 'Renamed';

        $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields)->assertRedirect();

        $this->assertSame('UA-1', globals('site')->field('analytics_id'));
        $this->assertStringContainsString('analytics_id: UA-1', (string) file_get_contents($this->file));
    }

    public function testAFieldLeftEmptyAddsNoKeyToTheFile(): void
    {
        $fields = $this->openForm();

        $fields['fields']['name'] = 'Renamed';

        $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields)->assertRedirect();

        $this->assertStringNotContainsString('tagline', (string) file_get_contents($this->file));
    }

    public function testASaveIsWrittenWholeAndLeavesNoTemporaryFileBehind(): void
    {
        $fields = $this->openForm();

        $fields['fields']['name'] = 'Renamed';

        $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields)->assertRedirect();

        $this->assertSame(['site.yaml'], array_map(basename(...), glob($this->site . 'globals/{,.}*.yaml', GLOB_BRACE)));
        $this->assertSame(['.', '..', 'site.yaml'], scandir($this->site . 'globals'));
    }

    public function testASaveKeepsTheFilesPermissions(): void
    {
        chmod($this->file, 0o640);
        $fields = $this->openForm();

        $fields['fields']['name'] = 'Renamed';

        $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields)->assertRedirect();

        clearstatcache();
        $this->assertSame(0o640, fileperms($this->file) & 0o777);
    }

    public function testSavingNothingToAnEmptyFileLeavesItEmpty(): void
    {
        $this->writeFile('globals/site.yaml', '');
        file_put_contents($this->blueprints . 'globals/site.yaml', "fields:\n  - handle: name\n    type: text\n");

        $fields = $this->openForm();

        $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields)->assertRedirect();

        $this->assertSame('', file_get_contents($this->file));
        $this->assertSame('x', globals('site')->field('name', 'x'));
    }

    // When the file changed

    public function testASaveAfterTheFileChangedOnDiskIsRefusedAndLeavesTheFileAlone(): void
    {
        $fields = $this->openForm();

        $theirs = "name: Somebody else's name\n";
        file_put_contents($this->file, $theirs);

        $fields['fields']['name'] = 'My name';
        $response                 = $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields);

        $response->assertStatus(409);
        $this->assertSame($theirs, file_get_contents($this->file));
        $this->assertCount(1, $this->query($response, '//*[@data-notice]//a[@data-reload]'));
        $this->assertSame(lang('Content.conflict'), trim(preg_replace('/\s+/', ' ', $this->query($response, '//*[@data-notice]')->item(0)->firstChild->textContent)));
        // The input is kept, so it isn't lost.
        $this->assertSame('My name', $this->query($response, '//input[@name="fields[name]"]')->item(0)->getAttribute('value'));
    }

    public function testASaveWithoutTheHashIsRefused(): void
    {
        $before = file_get_contents($this->file);

        $this->actingAs($this->admin)->post(route_to('content-global', 'site'), ['fields' => ['name' => 'No hash']])->assertStatus(409);

        $this->assertSame($before, file_get_contents($this->file));
    }

    public function testASaveAfterTheFileWasDeletedIsNotFound(): void
    {
        $fields = $this->openForm();

        unlink($this->file);

        $fields['fields']['name'] = 'Late';
        $this->expectException(PageNotFoundException::class);

        try {
            $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields);
        } finally {
            $this->assertFileDoesNotExist($this->file);
        }
    }

    // Validation

    public function testAValidationErrorShowsOnTheFormAndWritesNothing(): void
    {
        $fields = $this->openForm();
        $before = file_get_contents($this->file);

        $fields['fields']['name']           = '';
        $fields['fields']['posts_per_page'] = 'lots';
        $fields['fields']['tagline']        = 'Kept tagline';

        $response = $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields);

        $response->assertOK();
        $this->assertCount(1, $this->query($response, '//*[@data-error="name"]'));
        $this->assertCount(1, $this->query($response, '//*[@data-error="posts_per_page"]'));
        $this->assertCount(0, $this->query($response, '//*[@data-error="tagline"]'));
        $this->assertSame('Kept tagline', $this->query($response, '//input[@name="fields[tagline]"]')->item(0)->getAttribute('value'));
        $this->assertSame($before, file_get_contents($this->file));
    }

    public function testTheBlueprintsRulesApply(): void
    {
        $fields = $this->openForm();
        $before = file_get_contents($this->file);

        // max_length[20], and a number below the field's min.
        $fields['fields']['name']           = str_repeat('x', 21);
        $fields['fields']['posts_per_page'] = '0';

        $response = $this->actingAs($this->admin)->post(route_to('content-global', 'site'), $fields);

        $this->assertCount(1, $this->query($response, '//*[@data-error="name"]'));
        $this->assertCount(1, $this->query($response, '//*[@data-error="posts_per_page"]'));
        $this->assertSame($before, file_get_contents($this->file));
    }

    /**
     * Opens the form as a browser would, and gives what it would post.
     *
     * @return array<string, mixed>
     */
    private function openForm(): array
    {
        $response = $this->actingAs($this->admin)->get(route_to('content-global', 'site'));

        $response->assertOK();

        return $this->formFields($response->getBody(), url_to('content-global', 'site'));
    }

    private function userWith(string ...$permissions): User
    {
        $user = $this->createUser();
        $user->addPermission('admin.access', ...$permissions);

        return $user;
    }

    /**
     * @return DOMNodeList<DOMElement>
     */
    private function query(TestResponse $response, string $xpath): DOMNodeList
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $response->getBody());
        libxml_clear_errors();

        return (new DOMXPath($dom))->query($xpath);
    }
}
