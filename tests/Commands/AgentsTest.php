<?php

namespace Tests\Commands;

use Bonfire\Commands\Agents;
use CodeIgniter\Test\StreamFilterTrait;
use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AgentsTest extends TestCase
{
    use StreamFilterTrait;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        helper('filesystem');

        $this->directory = sys_get_temp_dir() . '/bonfire-' . bin2hex(random_bytes(8)) . '/';
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        delete_files($this->directory, true, true);
        rmdir($this->directory);

        parent::tearDown();
    }

    public function testCreatesAgentsFileWithTheSection()
    {
        $this->assertSame(0, $this->runCommand());

        $contents = file_get_contents($this->directory . 'AGENTS.md');

        $this->assertStringStartsWith('<!-- bonfire:start -->', $contents);
        $this->assertStringEndsWith("<!-- bonfire:end -->\n", $contents);
        $this->assertStringContainsString(InstalledVersions::getPrettyVersion('lonnieezell/bonfire'), (string) $contents);
        $this->assertStringNotContainsString('{version}', (string) $contents);
    }

    public function testAppendsTheSectionToAnAgentsFileWithoutMarkers()
    {
        file_put_contents($this->directory . 'AGENTS.md', "# My project\n\nUse tabs.\n");

        $this->assertSame(0, $this->runCommand());

        $contents = file_get_contents($this->directory . 'AGENTS.md');

        $this->assertStringStartsWith("# My project\n\nUse tabs.\n\n<!-- bonfire:start -->\n", $contents);
        $this->assertStringEndsWith("<!-- bonfire:end -->\n", $contents);
    }

    public function testReplacesOnlyTheTextBetweenTheMarkers()
    {
        file_put_contents(
            $this->directory . 'AGENTS.md',
            "# Before\n\n<!-- bonfire:start -->\nOld guidance.\n<!-- bonfire:end -->\n\n# After\n",
        );

        $this->assertSame(0, $this->runCommand());

        $contents = file_get_contents($this->directory . 'AGENTS.md');

        $this->assertStringStartsWith("# Before\n\n<!-- bonfire:start -->\n## Bonfire 2", $contents);
        $this->assertStringEndsWith("<!-- bonfire:end -->\n\n# After\n", $contents);
        $this->assertStringNotContainsString('Old guidance.', (string) $contents);
    }

    public function testRunningAgainLeavesTheFileUnchanged()
    {
        file_put_contents($this->directory . 'AGENTS.md', "# My project\n");

        $this->runCommand();
        $first = file_get_contents($this->directory . 'AGENTS.md');

        $this->runCommand();

        $this->assertSame($first, file_get_contents($this->directory . 'AGENTS.md'));
    }

    #[DataProvider('provideRefusesToWriteWhenTheMarkersAreBroken')]
    public function testRefusesToWriteWhenTheMarkersAreBroken(string $existing)
    {
        file_put_contents($this->directory . 'AGENTS.md', $existing);

        $this->assertSame(1, $this->runCommand());
        $this->assertSame($existing, file_get_contents($this->directory . 'AGENTS.md'));
    }

    public static function provideRefusesToWriteWhenTheMarkersAreBroken(): iterable
    {
        yield 'start without end' => ["<!-- bonfire:start -->\nMine.\n"];

        yield 'end without start' => ["Mine.\n<!-- bonfire:end -->\n"];

        yield 'end before start' => ["<!-- bonfire:end -->\nMine.\n<!-- bonfire:start -->\n"];

        yield 'duplicate start' => ["<!-- bonfire:start -->\n<!-- bonfire:start -->\nA\n<!-- bonfire:end -->\n"];

        yield 'duplicate section' => [
            "<!-- bonfire:start -->\nA\n<!-- bonfire:end -->\n<!-- bonfire:start -->\nB\n<!-- bonfire:end -->\n",
        ];
    }

    public function testFailsWithoutWritingWhenTheFileCannotBeRead()
    {
        file_put_contents($this->directory . 'AGENTS.md', "# Mine\n");
        chmod($this->directory . 'AGENTS.md', 0200);

        if (is_readable($this->directory . 'AGENTS.md')) {
            $this->markTestSkipped('File permissions are not enforced for this user.');
        }

        $this->assertSame(1, $this->runCommand());

        chmod($this->directory . 'AGENTS.md', 0600);
        $this->assertSame("# Mine\n", file_get_contents($this->directory . 'AGENTS.md'));
    }

    public function testFailsWhenTheFileCannotBeWritten()
    {
        mkdir($this->directory . 'AGENTS.md');

        $this->assertSame(1, $this->runCommand());
    }

    public function testHintsToImportAgentsFileFromClaudeFile()
    {
        file_put_contents($this->directory . 'CLAUDE.md', "# Notes\n");

        $this->runCommand();

        $this->assertStringContainsString('@AGENTS.md', $this->getStreamFilterBuffer());
        $this->assertSame("# Notes\n", file_get_contents($this->directory . 'CLAUDE.md'));
    }

    public function testNoHintWhenClaudeFileAlreadyImportsAgentsFile()
    {
        file_put_contents($this->directory . 'CLAUDE.md', "@AGENTS.md\n");

        $this->runCommand();

        $this->assertStringNotContainsString('@AGENTS.md', $this->getStreamFilterBuffer());
    }

    public function testNoHintWithoutClaudeFile()
    {
        $this->runCommand();

        $this->assertStringNotContainsString('@AGENTS.md', $this->getStreamFilterBuffer());
        $this->assertFileDoesNotExist($this->directory . 'CLAUDE.md');
    }

    public function testSuccessSuggestsRunningAfterComposerUpdate()
    {
        $this->runCommand();

        $output = $this->getStreamFilterBuffer();

        $this->assertStringContainsString('post-update-cmd', $output);
        $this->assertStringContainsString('php spark bf:agents', $output);
    }

    public function testEveryDocInTheMapExists()
    {
        preg_match_all('/`([\w\/]+\.md)`/', file_get_contents(BFPATH . 'Commands/Agents/bonfire.md'), $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $doc) {
            $this->assertFileExists(BFPATH . '../docs/' . $doc);
        }
    }

    private function runCommand(): int
    {
        $command = new class (service('logger'), service('commands')) extends Agents {
            public string $root;

            protected function rootPath(): string
            {
                return $this->root;
            }
        };
        $command->root = $this->directory;

        return $command->run([]);
    }
}
