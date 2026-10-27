<?php

namespace Tests\Tools;

use Bonfire\Users\User;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SystemInfoTest extends TestCase
{
    protected $refresh = true;
    private User $admin;
    private string $lockedFile = WRITEPATH . 'locked-by-test.txt';

    protected function setUp(): void
    {
        parent::setUp();

        setting('Site.siteOnline', true);

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        if (is_file($this->lockedFile)) {
            chmod($this->lockedFile, 0644);
            unlink($this->lockedFile);
        }

        parent::tearDown();
    }

    public function testShowsServerAndFilesystemCardsWithCopyButton(): void
    {
        // The raw body: the DOM-parsed getBody() drops Alpine's @-attributes.
        $html = $this->actingAs($this->admin)->get(route_to('sys-info'))->response()->getBody();

        $this->assertStringContainsString('<h2>Server</h2>', $html);
        $this->assertMatchesRegularExpression('#<div class="bf-kv-key">PHP Version</div>\s*<div class="bf-kv-value bf-mono">' . preg_quote(PHP_VERSION, '#') . '</div>#', $html);
        $this->assertStringContainsString('<h2>Filesystem</h2>', $html);
        $this->assertMatchesRegularExpression('#/writable</span>.*?bf-status-ok"><span class="bf-dot" aria-hidden="true"></span>Writable#s', $html);
        $this->assertMatchesRegularExpression('#<button type="button" class="btn btn-outline-secondary" @click="copy\(\)"#', $html);
        $this->assertStringNotContainsString('bf-callout-warn', $html);
    }

    public function testWarnsWhenSomethingIsNotWritable(): void
    {
        file_put_contents($this->lockedFile, 'x');
        chmod($this->lockedFile, 0444);

        if (is_really_writable($this->lockedFile)) {
            $this->markTestSkipped('This user can write to read-only files.');
        }

        $html = $this->actingAs($this->admin)->get(route_to('sys-info'))->getBody();

        $this->assertMatchesRegularExpression('#locked-by-test.txt</span>.*?bf-status-danger"><span class="bf-dot" aria-hidden="true"></span>Not writable#s', $html);
        $this->assertStringContainsString('bf-callout-warn', $html);
    }
}
