<?php

namespace Tests\Commands;

use Bonfire\Commands\Install\Publisher;
use InvalidArgumentException;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class InstallPublisherTest extends TestCase
{
    private string $source;
    private string $destination;

    protected function setUp(): void
    {
        parent::setUp();

        helper('filesystem');

        $root              = sys_get_temp_dir() . '/bonfire-' . bin2hex(random_bytes(8));
        $this->source      = $root . '/source';
        $this->destination = $root . '/themes';

        $this->writeFixture($this->source . '/Admin/master.php', 'bonfire master');
        $this->writeFixture($this->source . '/Admin/Components/button.php', 'bonfire button');
        $this->writeFixture($this->source . '/Auth/login.php', 'bonfire login');
    }

    protected function tearDown(): void
    {
        delete_files(dirname($this->source), true, true);
        rmdir(dirname($this->source));

        parent::tearDown();
    }

    public function testCopiesEveryThemeFileIntoAFreshFolder(): void
    {
        $result = (new Publisher())->copyDirectory($this->source, $this->destination);

        $this->assertSame(['copied' => 3, 'skipped' => 0, 'failed' => []], $result);
        $this->assertSame('bonfire master', file_get_contents($this->destination . '/Admin/master.php'));
        $this->assertSame('bonfire button', file_get_contents($this->destination . '/Admin/Components/button.php'));
        $this->assertSame('bonfire login', file_get_contents($this->destination . '/Auth/login.php'));
    }

    public function testKeepsAThemeFileYouHaveChanged(): void
    {
        $this->writeFixture($this->destination . '/Admin/master.php', 'my master');

        $result = (new Publisher())->copyDirectory($this->source, $this->destination);

        $this->assertSame(['copied' => 2, 'skipped' => 1, 'failed' => []], $result);
        $this->assertSame('my master', file_get_contents($this->destination . '/Admin/master.php'));
    }

    public function testStillAddsThemeFilesYouDoNotHaveYet(): void
    {
        $this->writeFixture($this->destination . '/Admin/master.php', 'my master');

        (new Publisher())->copyDirectory($this->source, $this->destination);

        $this->assertSame('bonfire button', file_get_contents($this->destination . '/Admin/Components/button.php'));
        $this->assertSame('bonfire login', file_get_contents($this->destination . '/Auth/login.php'));
    }

    public function testRunningTwiceChangesNothingTheSecondTime(): void
    {
        $publisher = new Publisher();
        $publisher->copyDirectory($this->source, $this->destination);
        $this->writeFixture($this->destination . '/Auth/login.php', 'my login');

        $result = $publisher->copyDirectory($this->source, $this->destination);

        $this->assertSame(['copied' => 0, 'skipped' => 3, 'failed' => []], $result);
        $this->assertSame('my login', file_get_contents($this->destination . '/Auth/login.php'));
    }

    public function testOverwritesWhenAskedTo(): void
    {
        $this->writeFixture($this->destination . '/Admin/master.php', 'my master');

        $result = (new Publisher())->copyDirectory($this->source, $this->destination, true);

        $this->assertSame(['copied' => 3, 'skipped' => 0, 'failed' => []], $result);
        $this->assertSame('bonfire master', file_get_contents($this->destination . '/Admin/master.php'));
    }

    public function testReportsFilesItCouldNotWrite(): void
    {
        // A file where the Admin folder should go.
        $this->writeFixture($this->destination . '/Admin', 'not a folder');

        $result = (new Publisher())->copyDirectory($this->source, $this->destination);

        $this->assertSame(1, $result['copied']);
        $this->assertEqualsCanonicalizing(
            [$this->destination . '/Admin/master.php', $this->destination . '/Admin/Components/button.php'],
            $result['failed'],
        );
    }

    public function testRejectsAMissingSourceInsteadOfCopyingNothing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Publisher())->copyDirectory($this->source . '/nowhere', $this->destination);
    }

    public function testPublishFileSaysWhatItDid(): void
    {
        $publisher = new Publisher();
        $from      = $this->source . '/Admin/master.php';
        $to        = $this->destination . '/Admin/master.php';

        $this->assertSame('copied', $publisher->publishFile($from, $to));
        $this->assertSame('skipped', $publisher->publishFile($from, $to));
        $this->assertSame('overwritten', $publisher->publishFile($from, $to, true));
    }

    public function testRejectsAMissingDestinationWithoutExiting(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Publisher())->setDestination($this->destination . '/nowhere/');
    }

    private function writeFixture(string $path, string $contents): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);
    }
}
