<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions\Widgets;

use Bonfire\Actions\Models\ActionRunModel;
use Bonfire\Actions\RunStatus;
use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Dashboard\WidgetField;
use Bonfire\Dashboard\WidgetValues;
use Bonfire\Users\User;
use CodeIgniter\I18n\Time;

/**
 * The "Action runs" dashboard widget: the viewer's own queued runs that need
 * attention, newest first, each linking to its progress.
 */
class ActionRunsWidget implements DashboardWidget
{
    public const ID = 'actions:runs';

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Actions.actionRuns');
    }

    public function icon(): string
    {
        return 'fas fa-tasks';
    }

    public function group(): string
    {
        return lang('Actions.actionsModTitle');
    }

    public function description(): string
    {
        return lang('Actions.actionRunsCard');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Half, Size::Third);
    }

    public function permission(): ?string
    {
        return null;
    }

    public function fields(): array
    {
        return [
            WidgetField::number('count', lang('Actions.actionRunsCount'), default: 5, min: 1, max: 20),
            WidgetField::number('days', lang('Actions.actionRunsDays'), default: 7, min: 1, max: 30),
            WidgetField::toggle('completed', lang('Actions.actionRunsDone'), false),
        ];
    }

    /**
     * The runs $viewer started that are queued or running, or that failed
     * (or, with $completed, ended at all) in the last $days, newest first.
     * An ended run was last updated when it ended.
     *
     * @return list<array<string, mixed>> Rows of action_runs
     */
    public function runs(User $viewer, int $count, int $days, bool $completed): array
    {
        $ended = $completed ? [RunStatus::Failed->value, RunStatus::Done->value] : [RunStatus::Failed->value];

        return model(ActionRunModel::class)
            ->select('id, list, action, status, processed, skipped, total, created_at')
            ->where('user_id', $viewer->id)
            ->groupStart()
            ->whereIn('status', [RunStatus::Queued->value, RunStatus::Running->value])
            ->orGroupStart()
            ->whereIn('status', $ended)
            ->where('updated_at >=', Time::now()->subDays($days)->toDateTimeString())
            ->groupEnd()
            ->groupEnd()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->findAll($count);
    }

    public function render(WidgetValues $values): string
    {
        $runs = $this->runs(auth()->user(), (int) $values->get('count'), (int) $values->get('days'), (bool) $values->get('completed'));

        return view('Bonfire\Actions\Views\widgets\action_runs', [
            'title' => $this->title(),
            'runs'  => array_map(static fn (array $run) => [...$run, 'label' => service('actionRuns')->label($run)], $runs),
        ]);
    }
}
