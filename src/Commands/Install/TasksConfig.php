<?php

namespace Bonfire\Commands\Install;

/**
 * Adds Bonfire's scheduled tasks to the app's Config\Tasks,
 * creating the file when the app has none.
 */
class TasksConfig
{
    private const CALL = 'Bonfire::schedule($schedule);';

    /**
     * @return 'created'|'manual'|'unchanged'|'updated'
     */
    public function addSchedule(string $path): string
    {
        if (! is_file($path)) {
            file_put_contents($path, <<<'PHP'
                <?php

                namespace Config;

                use Bonfire\Bonfire;
                use CodeIgniter\Tasks\Config\Tasks as BaseTasks;
                use CodeIgniter\Tasks\Scheduler;

                class Tasks extends BaseTasks
                {
                    /**
                     * Register any tasks within this method for the application.
                     * Called by the TaskRunner.
                     */
                    public function init(Scheduler $schedule)
                    {
                        Bonfire::schedule($schedule);
                    }
                }

                PHP);

            return 'created';
        }

        $content = file_get_contents($path);

        if (str_contains($content, self::CALL)) {
            return 'unchanged';
        }

        $output = preg_replace_callback(
            '/public function init\(Scheduler \$schedule\)(?::\s*void)?\s*\{\n/',
            static fn (array $match) => $match[0] . '        \\Bonfire\\' . self::CALL . "\n",
            $content,
            1,
            $count,
        );

        if ($count === 0) {
            return 'manual';
        }

        file_put_contents($path, $output);

        return 'updated';
    }
}
