<?php

namespace Bonfire\Dashboard;

use CodeIgniter\I18n\Time;
use IntlDatePatternGenerator;

class DashboardCells
{
    public function quickLinks()
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
