<?php

declare(strict_types=1);

namespace Tests\Email;

use Bonfire\Users\User;
use CodeIgniter\I18n\Time;
use Config\Services;
use Tests\Support\RendersDashboard;
use Tests\Support\TestCase;

/**
 * The Outbox's sent and failed counts for the last 7 days, as dashboard stat cards.
 *
 * @internal
 */
final class MailStatsWidgetTest extends TestCase
{
    use RendersDashboard;

    private const SENT   = 'stats:mailSent625';
    private const FAILED = 'stats:mailFailed625';

    protected $refresh = true;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetModules();
        setting('Site.siteOnline', true);
        setting('Email.logRetentionDays', 30);

        $this->admin = $this->createUser();
        $this->admin->addGroup('superadmin');
    }

    protected function tearDown(): void
    {
        $this->forgetDashboardState();
        $this->resetModules();

        parent::tearDown();
    }

    public function testCountsSentAndFailedMailFromTheLastSevenDays(): void
    {
        $this->log('sent', 1);
        $this->log('sent', 6);
        $this->log('sent', 8);
        $this->log('failed', 2);
        $this->log('failed', 9);
        $this->log('failed', 30);
        $this->placeOnDashboard($this->admin, self::SENT, self::FAILED);

        $html = $this->dashboardHtml($this->admin);

        $this->assertSame('2', $this->valueOf($html, self::SENT));
        $this->assertSame('1', $this->valueOf($html, self::FAILED));
    }

    public function testFailedLinksToTheOutboxFilteredToFailures(): void
    {
        $this->placeOnDashboard($this->admin, self::FAILED);

        $html = $this->dashboardHtml($this->admin);

        $url = site_url(ADMIN_AREA . '/tools/mail/outbox?status=failed&date_from=' . Time::today()->subDays(7)->toDateString());

        $this->assertStringContainsString('href="' . esc($url) . '"', $this->tile($html, self::FAILED));
    }

    public function testAFailedCountAboveZeroIsHighlighted(): void
    {
        $this->log('failed', 1);
        $this->placeOnDashboard($this->admin, self::SENT, self::FAILED);

        $html = $this->dashboardHtml($this->admin);

        $this->assertStringContainsString('bf-stat-alert', $this->tile($html, self::FAILED));
        $this->assertStringNotContainsString('bf-stat-alert', $this->tile($html, self::SENT));
    }

    public function testAFailedCountOfZeroIsNotHighlighted(): void
    {
        $this->log('sent', 1);
        $this->placeOnDashboard($this->admin, self::FAILED);

        $this->assertStringNotContainsString('bf-stat-alert', $this->tile($this->dashboardHtml($this->admin), self::FAILED));
    }

    public function testNoQueryRunsWhileTheCardsAreNotPlaced(): void
    {
        db_connect()->query('DROP TABLE ' . db_connect()->prefixTable('email_log'));

        $this->actingAs($this->admin)->get(ADMIN_AREA)->assertOK();
    }

    public function testAMissingOutboxTableDoesNotBreakThePlacedCards(): void
    {
        db_connect()->query('DROP TABLE ' . db_connect()->prefixTable('email_log'));
        $this->placeOnDashboard($this->admin, self::SENT, self::FAILED);

        $this->actingAs($this->admin)->get(ADMIN_AREA)->assertOK();
    }

    public function testHiddenFromAnAdminWithoutTheOutboxPermission(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');
        $this->placeOnDashboard($user, self::SENT, self::FAILED);

        $this->assertSame([], $this->placedIds($user));
    }

    public function testHiddenWhileTheOutboxIsOff(): void
    {
        setting('Email.logRetentionDays', 0);
        $this->resetModules();
        $this->placeOnDashboard($this->admin, self::SENT, self::FAILED);

        $this->assertSame([], $this->placedIds($this->admin));
    }

    /**
     * Modules add their cards to these services each request, so a test starts them over.
     */
    private function resetModules(): void
    {
        foreach (['auth', 'bonfire', 'menus', 'widgets', 'dashboard'] as $service) {
            Services::resetSingle($service);
        }
    }

    private function log(string $status, int $daysAgo): void
    {
        db_connect()->table('email_log')->insert([
            'status'     => $status,
            'to'         => '[]',
            'created_at' => Time::now()->subDays($daysAgo)->toDateTimeString(),
        ]);
    }

    /**
     * The markup of one placed widget, from its tile to the next.
     */
    private function tile(string $html, string $id): string
    {
        $html  = $this->placedHtml($html);
        $start = strpos($html, 'data-widget-id="' . str_replace(':', '&#x3A;', $id) . '"');
        $this->assertNotFalse($start, "{$id} is not on the dashboard");

        $next = strpos($html, 'data-widget-id="', $start + 1);

        return substr($html, $start, $next === false ? null : $next - $start);
    }

    private function valueOf(string $html, string $id): string
    {
        preg_match('#<p class="bf-stat-value">(.*?)</p>#s', $this->tile($html, $id), $matches);

        return trim(strip_tags($matches[1] ?? ''));
    }
}
