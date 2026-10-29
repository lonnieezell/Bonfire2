<?php

namespace Tests\Tools;

use Bonfire\Dashboard\WidgetValues;
use Bonfire\Tools\Libraries\SystemHealth;
use Bonfire\Tools\Widgets\SystemHealthWidget;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SystemHealthWidgetTest extends TestCase
{
    private SystemHealth $health;

    public function testListsEachProblemAndLinksToSystemInfo(): void
    {
        $html = $this->render([
            ['problem' => 'notWritable', 'subject' => 'cache'],
            ['problem' => 'envMissing', 'subject' => '.env'],
        ]);

        $this->assertSame(2, substr_count($html, '<li'));
        $this->assertStringContainsString('cache', $html);
        $this->assertStringContainsString('href="' . url_to('sys-info') . '"', $html);
        $this->assertStringNotContainsString('bf-rows-empty', $html);
    }

    public function testAHealthyInstallShowsASingleAllGoodState(): void
    {
        $html = $this->render([]);

        $this->assertStringContainsString('bf-rows-empty', $html);
        $this->assertStringNotContainsString('<li', $html);
    }

    public function testPassesTheLoadLimitSettingToTheChecks(): void
    {
        $this->render([], ['maxLoad' => 8]);

        $this->assertEqualsWithDelta(8.0, $this->health->maxLoad, PHP_FLOAT_EPSILON);
    }

    public function testNeedsAdminSettingsAndWarnsAboveALoadOfFourByDefault(): void
    {
        $widget = new SystemHealthWidget();

        $this->assertSame('tools:systemHealth', $widget->id());
        $this->assertSame('admin.settings', $widget->permission());
        $this->assertSame(['maxLoad' => 4.0], WidgetValues::resolve($widget->fields())->all());
    }

    /**
     * @param list<array{problem: string, subject: string}> $problems
     * @param array<string, float|int>                      $settings
     */
    private function render(array $problems, array $settings = []): string
    {
        $this->health = new class ($problems) extends SystemHealth {
            public ?float $maxLoad = null;

            public function __construct(private readonly array $found)
            {
                parent::__construct();
            }

            public function problems(float $maxLoad): array
            {
                $this->maxLoad = $maxLoad;

                return $this->found;
            }
        };
        $widget = new SystemHealthWidget($this->health);

        return $widget->render(WidgetValues::resolve($widget->fields(), $settings));
    }
}
