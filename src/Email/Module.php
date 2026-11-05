<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Email;

use Bonfire\Core\BaseModule;
use Bonfire\Email\Models\EmailLogModel;
use Bonfire\Menus\MenuItem;
use Bonfire\Widgets\Types\Stats\StatsItem;
use CodeIgniter\I18n\Time;
use Throwable;

/**
 * Email Module setup
 */
class Module extends BaseModule
{
    /**
     * Setup our admin area needs.
     */
    public function initAdmin()
    {
        // Settings menu for sidebar
        $sidebar = service('menus');
        $item    = new MenuItem([
            'title'           => lang('Email.emailModTitle'),
            'namedRoute'      => 'email-settings',
            'fontAwesomeIcon' => 'fas fa-envelope',
            'permission'      => 'admin.settings',
        ]);
        $sidebar->menu('sidebar')->collection('settings')->addItem($item);

        $sidebar->menu('sidebar')->collection('tools')->addItem(new MenuItem([
            'title'           => lang('Email.previewsModTitle'),
            'namedRoute'      => 'mail-previews',
            'fontAwesomeIcon' => 'fas fa-envelope-open-text',
            'permission'      => 'email.preview',
        ]));

        $sidebar->menu('sidebar')->collection('tools')->addItem(new MenuItem([
            'title'           => lang('Email.outboxModTitle'),
            'namedRoute'      => 'mail-outbox',
            'fontAwesomeIcon' => 'fas fa-paper-plane',
            'permission'      => 'email.log',
        ]));

        $this->addOutboxStats();
    }

    /**
     * Sent and failed counts for the last 7 days, while the Outbox is on.
     */
    private function addOutboxStats(): void
    {
        if (Outbox::retentionDays() === 0) {
            return;
        }

        $stats = service('widgets')->widget('stats')->collection('stats');
        $since = Time::today()->subDays(7);

        $stats->addItem($this->outboxStat('sent', 'mailSent625', 'fas fa-paper-plane', $since));
        $stats->addItem($this->outboxStat('failed', 'mailFailed625', 'fas fa-triangle-exclamation', $since));
    }

    /**
     * Counts from midnight, so the card and the Outbox its link opens, which filters by date, agree.
     */
    private function outboxStat(string $status, string $id, string $icon, Time $since): StatsItem
    {
        $from = $since->toDateTimeString();

        return (new StatsItem([
            'id'               => $id,
            'title'            => lang('Email.stats' . ucfirst($status)),
            'url'              => ADMIN_AREA . '/tools/mail/outbox?' . http_build_query(['status' => $status, 'date_from' => $since->toDateString()]),
            'faIcon'           => $icon,
            'permission'       => 'email.log',
            'module'           => lang('Email.emailModTitle'),
            'highlightNonZero' => $status === 'failed',
        ]))->addValueUsing(static function () use ($status, $from): int {
            // Like the Outbox's own writes, a missing table must not take the page down.
            try {
                return (new EmailLogModel())->filter(['status' => $status])->where('created_at >=', $from)->countAllResults();
            } catch (Throwable $e) {
                log_message('error', 'The Outbox could not be counted: {message}', ['message' => $e->getMessage()]);

                return 0;
            }
        });
    }
}
