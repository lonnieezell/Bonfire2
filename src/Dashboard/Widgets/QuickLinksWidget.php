<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard\Widgets;

use Bonfire\Dashboard\DashboardCells;
use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Dashboard\WidgetValues;
use CodeIgniter\I18n\Time;
use IntlDatePatternGenerator;

/**
 * A greeting, plus a link to each item of the "content" sidebar menu
 * the viewer may see.
 *
 * It keeps the id of the Dashboard.cells entry it replaced, so saved
 * layouts and installs that still list that cell keep it in place.
 */
class QuickLinksWidget implements DashboardWidget
{
    public const ID = 'cell:' . DashboardCells::class . '::quickLinks';

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Bonfire.quickLinks');
    }

    public function icon(): string
    {
        return 'fas fa-link';
    }

    public function group(): string
    {
        return lang('Bonfire.dashboard');
    }

    public function description(): string
    {
        return lang('Bonfire.quickLinksCard');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Full);
    }

    public function permission(): ?string
    {
        return null;
    }

    public function fields(): array
    {
        return [];
    }

    public function render(WidgetValues $values): string
    {
        $now  = Time::now();
        $user = auth()->user();
        $name = $user->first_name ?: $user->username;

        $greeting = match (true) {
            $now->getHour() < 12 => 'goodMorning',
            $now->getHour() < 18 => 'goodAfternoon',
            default              => 'goodEvening',
        };

        return view('Bonfire\Dashboard\Views\quick_links', [
            'menu'     => service('menus')->menu('sidebar')->collection('content'),
            'today'    => $now->toLocalizedString((new IntlDatePatternGenerator(service('request')->getLocale()))->getBestPattern('EEEEMMMMd')),
            'greeting' => lang('Bonfire.' . $greeting, [$name]),
        ]);
    }
}
