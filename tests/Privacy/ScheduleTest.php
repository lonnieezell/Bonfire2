<?php

namespace Tests\Privacy;

use Bonfire\Bonfire;
use Bonfire\Commands\Install\TasksConfig;
use CodeIgniter\Tasks\Scheduler;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ScheduleTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir() . '/bf-tasks-' . bin2hex(random_bytes(4)) . '.php';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function testSchedulesTheDailyPrune(): void
    {
        $schedule = new Scheduler();

        Bonfire::schedule($schedule);

        $tasks = array_filter($schedule->getTasks(), static fn ($task) => $task->getAction() === 'bf:privacy:prune');
        $this->assertCount(1, $tasks);
        $this->assertSame('command', reset($tasks)->getType());
        $this->assertSame('0 0 * * *', reset($tasks)->getExpression());
    }

    public function testInstallCreatesTheTasksConfig(): void
    {
        $this->assertSame('created', (new TasksConfig())->addSchedule($this->path));

        $this->assertStringContainsString('Bonfire::schedule($schedule);', (string) file_get_contents($this->path));
        $this->assertSame(['bf:privacy:prune'], $this->scheduledBy($this->path));
    }

    public function testInstallAddsTheLineToAnExistingConfigOnce(): void
    {
        file_put_contents($this->path, <<<'PHP'
            <?php

            namespace Config;

            use CodeIgniter\Tasks\Config\Tasks as BaseTasks;
            use CodeIgniter\Tasks\Scheduler;

            class Tasks extends BaseTasks
            {
                public function init(Scheduler $schedule)
                {
                    $schedule->command('app:report')->daily();
                }
            }
            PHP);

        $this->assertSame('updated', (new TasksConfig())->addSchedule($this->path));
        $this->assertSame('unchanged', (new TasksConfig())->addSchedule($this->path));

        $this->assertSame(1, substr_count(file_get_contents($this->path), 'Bonfire::schedule($schedule);'));
        $this->assertSame(['bf:privacy:prune', 'app:report'], $this->scheduledBy($this->path));
    }

    public function testInstallLeavesAConfigItCannotReadAlone(): void
    {
        file_put_contents($this->path, "<?php\n// something unusual\n");

        $this->assertSame('manual', (new TasksConfig())->addSchedule($this->path));
        $this->assertSame("<?php\n// something unusual\n", file_get_contents($this->path));
    }

    /**
     * Loads the written config under a unique class name and lists what it schedules.
     *
     * @return list<string>
     */
    private function scheduledBy(string $path): array
    {
        $class = 'Tasks' . bin2hex(random_bytes(4));
        eval('?>' . str_replace('class Tasks ', "class {$class} ", file_get_contents($path)));

        $schedule = new Scheduler();
        (new ('Config\\' . $class)())->init($schedule);

        return array_map(static fn ($task) => $task->getAction(), $schedule->getTasks());
    }
}
