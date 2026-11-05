<?php

declare(strict_types=1);

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Assets;

use Bonfire\Assets\AssetLocator;
use CodeIgniter\Config\Factories;
use CodeIgniter\Exceptions\PageNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * A URL that asset() builds has to be one that AssetController serves.
 *
 * @internal
 */
final class AssetControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->mockCache();
        parent::setUp();

        helper(['Bonfire\Assets\Helpers\assets']);
    }

    #[DataProvider('provideBuiltUrlIsServed')]
    public function testBuiltUrlIsServed(string $bustingType, string $separator): void
    {
        $this->useConfig($bustingType, $separator);

        $url = asset('admin/css/admin.css', 'css');

        $this->assertStringContainsString("admin{$separator}", $url);
        $this->assertServes(BFPATH . '../themes/Admin/css/admin.css', $this->pathOf($url));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideBuiltUrlIsServed(): iterable
    {
        yield 'file' => ['file', '~~'];

        yield 'version' => ['version', '~~'];

        yield 'custom separator' => ['file', '--v-'];
    }

    public function testServedFileIsTheOneTheUrlNames(): void
    {
        $this->useConfig('file', '~~');

        $this->assertServes(BFPATH . '../themes/Admin/js/admin.js', $this->pathOf(asset('admin/js/admin.js', 'js')));
    }

    public function testUrlWithoutFingerprintIsServed(): void
    {
        $this->assertServes(BFPATH . '../themes/Admin/css/admin.css', '/assets/admin/css/admin.css');
    }

    public function testMissingAssetIsNotFound(): void
    {
        $this->useConfig('file', '~~');

        $url = asset('admin/css/admin_missing.css', 'css');

        $this->assertStringContainsString('asset-is-missing', $url);
        $this->expectException(PageNotFoundException::class);
        $this->get($this->pathOf($url));
    }

    public function testUnknownFolderIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('/assets/nope/css/admin.css');
    }

    public function testFilenameWithoutExtensionIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('/assets/admin/css/admin');
    }

    public function testUnknownFolderGivesAMissingAssetUrlInsteadOfAnError(): void
    {
        $this->assertStringContainsString('asset-is-missing', asset('nope/css/admin.css', 'css'));
        $this->assertStringContainsString('asset-is-missing', asset('admin.css', 'css'));
    }

    public function testLocatorRefusesToLeaveTheFolder(): void
    {
        $locator = new AssetLocator();

        $this->assertNotNull($locator->locate(['admin', 'css', 'admin.css']));
        $this->assertNull($locator->locate(['admin', '..', 'Admin', 'css', 'admin.css']));
        $this->assertNull($locator->locate(['admin', 'css', '..', '..', '..', '..', 'composer.json']));
        $this->assertNull($locator->locate(['admin', 'css\\..\\admin.css']));
        $this->assertNull($locator->locate(["admin\0", 'css', 'admin.css']));
        $this->assertNull($locator->locate([]));
    }

    public function testDefaultSeparatorIsUsedWhenNoneIsConfigured(): void
    {
        $config            = config('Assets');
        $config->separator = null;
        Factories::injectMock('config', 'Assets', $config);

        $this->assertNotNull((new AssetLocator())->locate(['admin', 'css', 'admin~~123.css']));
    }

    public function testAssetLinkEscapesItsOutput(): void
    {
        $link = asset_link('admin/css/admin.css', 'css', ['data-x' => "a'b<c>"]);

        $this->assertStringContainsString("data-x='a&#039;b&lt;c&gt;'", $link);
        $this->assertStringNotContainsString('<c>', $link);
    }

    private function useConfig(string $bustingType, string $separator): void
    {
        $config              = config('Assets');
        $config->bustingType = $bustingType;
        $config->separator   = $separator;
        Factories::injectMock('config', 'Assets', $config);
    }

    private function assertServes(string $file, string $urlPath): void
    {
        $response = $this->get($urlPath)->response();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(strlen((string) file_get_contents($file)), $response->getContentLength());
    }

    private function pathOf(string $url): string
    {
        return (string) parse_url($url, PHP_URL_PATH);
    }
}
