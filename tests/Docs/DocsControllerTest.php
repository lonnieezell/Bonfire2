<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Docs;

use Bonfire\Docs\Libraries\Documentation;
use Bonfire\Users\User;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DocsControllerTest extends TestCase
{
    protected $refresh = true;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');

        $this->useEnvironment('development');

        foreach (['docs/zebra', 'docs/apple'] as $folder) {
            if (! is_dir(WRITEPATH . 'docs-order/' . $folder)) {
                mkdir(WRITEPATH . 'docs-order/' . $folder, 0755, true);
            }
        }

        foreach (['index.md', 'zebra/one.md', 'apple/one.md'] as $file) {
            file_put_contents(WRITEPATH . 'docs-order/docs/' . $file, "# Page\n");
        }
    }

    protected function tearDown(): void
    {
        helper('filesystem');
        delete_files(WRITEPATH . 'docs-order/', true);
        rmdir(WRITEPATH . 'docs-order');

        parent::tearDown();
    }

    public function testHomeRendersTheIndexPage(): void
    {
        $result = $this->actingAs($this->admin)->get(ADMIN_AREA . '/docs');

        $result->assertOK();
        $result->assertSee('home-page');
    }

    public function testShowsAPageInASectionByItsPath(): void
    {
        $result = $this->actingAs($this->admin)->get(ADMIN_AREA . '/docs/guide/setup');

        $result->assertOK();
        $result->assertSee('guide-setup');
    }

    public function testASectionUriShowsItsFirstPage(): void
    {
        $this->actingAs($this->admin)->get(ADMIN_AREA . '/docs/guide')->assertSee('guide-index');
    }

    public function testPagesOutsideTheNavAreNotFound(): void
    {
        foreach (['adr/private', 'guide/missing', '../mkdocs', 'guide/../adr/private'] as $uri) {
            $this->assertNotFound($uri);
        }
    }

    public function testProductionServesNothing(): void
    {
        $this->useEnvironment('production');

        $this->assertNotFound('');
        $this->assertNotFound('guide/setup');
    }

    public function testNonProductionEnvironmentsServeTheGuide(): void
    {
        $this->useEnvironment('staging');

        $this->actingAs($this->admin)->get(ADMIN_AREA . '/docs')->assertOK();
    }

    public function testRequiresAdminAccess(): void
    {
        $user = $this->createUser();
        $user->addGroup('user');

        $result = $this->actingAs($user)->get(ADMIN_AREA . '/docs/guide/setup');

        $result->assertRedirect();
        $this->assertStringNotContainsString('guide-setup', (string) $result->response()->getBody());
    }

    public function testGuestsAreSentAway(): void
    {
        $result = $this->get(ADMIN_AREA . '/docs/guide/setup');

        $result->assertRedirect();
        $this->assertStringNotContainsString('guide-setup', (string) $result->response()->getBody());
    }

    public function testSideNavListsTheSectionsPagesAndMarksTheCurrentOne(): void
    {
        $html = (string) $this->actingAs($this->admin)->get(ADMIN_AREA . '/docs/guide/setup')->response()->getBody();

        $this->assertMatchesRegularExpression('#href="[^"]*/docs/guide/setup" aria-current="page"#', $html);
        $this->assertStringContainsString('/docs/guide/named"', $html);
        $this->assertStringContainsString('Custom title', $html);
    }

    public function testSideNavGroupsANestedSectionsPages(): void
    {
        $html = (string) $this->actingAs($this->admin)->get(ADMIN_AREA . '/docs/guide/setup')->response()->getBody();

        $this->assertMatchesRegularExpression('#<details class="bf-docs-nav-group">\s*<summary[^>]*>\s*Advanced\s*</summary>\s*<a [^>]*href="[^"]*/docs/guide/advanced/tuning"#', $html);
    }

    public function testSideNavOpensTheGroupOfTheCurrentPage(): void
    {
        $result = $this->actingAs($this->admin)->get(ADMIN_AREA . '/docs/guide/advanced/tuning');

        $result->assertSee('guide-tuning');
        $this->assertMatchesRegularExpression('#<details class="bf-docs-nav-group" open>\s*<summary[^>]*>\s*Advanced\s*</summary>\s*<a [^>]*href="[^"]*/docs/guide/advanced/tuning" aria-current="page"#', (string) $result->response()->getBody());
    }

    public function testSidebarLinksHomeAndEachSection(): void
    {
        $html = $this->sidebarOn(ADMIN_AREA);

        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/docs') . '"', $html);
        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/docs/guide') . '"', $html);
        $this->assertStringContainsString('href="' . site_url(ADMIN_AREA . '/docs/reference') . '"', $html);
    }

    public function testSidebarKeepsTheOrderOfTheNav(): void
    {
        file_put_contents(WRITEPATH . 'docs-order/mkdocs.yml', <<<'YAML'
            nav:
              - Home: index.md
              - Zebra:
                  - zebra/one.md
              - Apple:
                  - apple/one.md
            YAML);

        Services::injectMock('docs', new Documentation(WRITEPATH . 'docs-order/', 'development'));

        $html = $this->sidebarOn(ADMIN_AREA);

        $home  = strpos($html, 'href="' . site_url(ADMIN_AREA . '/docs') . '"');
        $zebra = strpos($html, '/docs/zebra"');
        $apple = strpos($html, '/docs/apple"');

        $this->assertNotFalse($home);
        $this->assertNotFalse($zebra);
        $this->assertNotFalse($apple);
        $this->assertLessThan($zebra, $home);
        $this->assertLessThan($apple, $zebra);
    }

    public function testSidebarMarksTheSectionOfTheCurrentPage(): void
    {
        $html = $this->sidebarOn(ADMIN_AREA . '/docs/guide/setup');

        $this->assertMatchesRegularExpression('#bf-subnav-link active" href="[^"]*/docs/guide"#', $html);
        $this->assertDoesNotMatchRegularExpression('#bf-subnav-link active" href="[^"]*/docs"#', $html);
    }

    public function testSidebarHidesTheGuideInProduction(): void
    {
        $this->useEnvironment('production');

        $this->assertStringNotContainsString('/docs', $this->sidebarOn(ADMIN_AREA));
    }

    public function testSidebarHidesTheGuideWhenItsFilesAreMissing(): void
    {
        Services::injectMock('docs', new Documentation(SUPPORTPATH . 'NoSuchFolder/', 'development'));

        $this->assertStringNotContainsString('/docs', $this->sidebarOn(ADMIN_AREA));
    }

    public function testNoPagesAreServedWhenTheFilesAreMissing(): void
    {
        Services::injectMock('docs', new Documentation(SUPPORTPATH . 'NoSuchFolder/', 'development'));

        $this->assertNotFound('');
    }

    public function testBadYamlHidesTheGuideAndLogsWhy(): void
    {
        file_put_contents(WRITEPATH . 'docs-order/mkdocs.yml', "nav:\n  - [unclosed\n");

        $docs = new Documentation(WRITEPATH . 'docs-order/', 'development');

        $this->assertFalse($docs->isAvailable());
        $this->assertLogContains('error', 'mkdocs.yml');
    }

    private function sidebarOn(string $url): string
    {
        $html = (string) $this->actingAs($this->admin)->get($url)->response()->getBody();

        $this->assertMatchesRegularExpression('#<nav class="bf-nav">(.*?)</nav>#s', $html);
        preg_match('#<nav class="bf-nav">(.*?)</nav>#s', $html, $matches);

        return $matches[1];
    }

    private function assertNotFound(string $uri): void
    {
        try {
            $result = $this->actingAs($this->admin)->get(ADMIN_AREA . '/docs/' . $uri);
            $this->assertSame(404, $result->response()->getStatusCode(), $uri);
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }

    private function useEnvironment(string $environment): void
    {
        Services::injectMock('docs', new Documentation(SUPPORTPATH . 'DocsSite/', $environment));
    }
}
