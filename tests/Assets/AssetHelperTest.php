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

use CodeIgniter\Config\Factories;
use InvalidArgumentException;
use RuntimeException;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AssetHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $this->mockCache();
        parent::setUp();

        helper(['Bonfire\Assets\Helpers\assets']);
    }

    public function testAssetThrowsNoFilenameExtension(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You must provide a valid filename and extension to the asset() helper.');

        asset_link('foo', 'css');
    }

    public function testAssetLinkContainsMissingSubstring()
    {
        $result = asset_link('/admin/css/admin_missing.css', 'css');
        $this->assertStringContainsString('asset-is-missing', $result);
    }

    public function testAssetThrowsEmptyLocation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        asset_link('/', 'css');
    }

    public function testAssetThrowsNoExtension(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You must provide a valid filename and extension to the asset() helper.');

        asset_link('admin/foo', 'css');
    }

    public function testAssetIfIncorrectType(): void
    {
        $this->assertEmpty(asset_link('/admin/css/admin.css', 'map'));
    }

    public function testAssetThrowsLocationAsFullURL(): void
    {
        $this->expectException(InvalidArgumentException::class);

        asset_link('http://example.com/admin/css/admin.css', 'css');
    }

    public function testAssetVersion(): void
    {
        $config = config('Assets');

        $config->bustingType = 'version';
        $config->separator   = '~~';
        Factories::injectMock('config', 'Assets', $config);

        $link = asset_link('admin/css/admin.css', 'css');

        // In testing environment, would be the current timestamp
        // so just test the pattern to ensure that works.
        preg_match('|assets/admin/css/admin~~([\d]+).css|i', $link, $matches);

        $this->assertIsNumeric($matches[1]);
    }

    public function testAssetFile(): void
    {
        $config = config('Assets');

        $config->bustingType = 'file';
        $config->separator   = '~~';
        Factories::injectMock('config', 'Assets', $config);

        $link = asset_link('admin/css/admin.css', 'css');

        // In testing environment, would be the current timestamp
        // so just test the pattern to ensure that works.
        preg_match('|assets/admin/css/admin~~([\d]+).css|i', $link, $matches);

        $this->assertSame(filemtime(BFPATH . '../themes/Admin/css/admin.css'), (int) $matches[1]);
    }
}
