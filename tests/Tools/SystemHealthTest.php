<?php

namespace Tests\Tools;

use Bonfire\Tools\Libraries\SystemHealth;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SystemHealthTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/bonfire-health-' . uniqid('', true) . '/';
        mkdir($this->dir . 'cache', 0777, true);
        file_put_contents($this->dir . '.env', '');
    }

    protected function tearDown(): void
    {
        @unlink($this->dir . '.env');
        @chmod($this->dir . 'locked.txt', 0644);
        @unlink($this->dir . 'locked.txt');
        rmdir($this->dir . 'cache');
        rmdir($this->dir);

        parent::tearDown();
    }

    public function testANonWritablePathIsAProblem(): void
    {
        file_put_contents($this->dir . 'locked.txt', 'x');
        chmod($this->dir . 'locked.txt', 0444);

        if (is_really_writable($this->dir . 'locked.txt')) {
            $this->markTestSkipped('This user can write to read-only files.');
        }

        $this->assertSame([['problem' => 'notWritable', 'subject' => 'locked.txt']], $this->health()->problems(4.0));
    }

    public function testAMissingEnvFileIsAProblem(): void
    {
        unlink($this->dir . '.env');

        $this->assertSame([['problem' => 'envMissing', 'subject' => '.env']], $this->health()->problems(4.0));
    }

    public function testServerLoadAboveTheLimitIsAProblem(): void
    {
        $this->assertSame([['problem' => 'highLoad', 'subject' => '4.2']], $this->health(4.2)->problems(4.0));
        $this->assertSame([], $this->health(4.0)->problems(4.0));
        $this->assertSame([], $this->health(null)->problems(4.0));
    }

    private function health(?float $load = 0.5): SystemHealth
    {
        return new class ($this->dir, $this->dir . '.env', $load) extends SystemHealth {
            public function __construct(string $writablePath, string $envFile, private readonly ?float $load)
            {
                parent::__construct($writablePath, $envFile);
            }

            public function serverLoad(): ?float
            {
                return $this->load;
            }
        };
    }
}
