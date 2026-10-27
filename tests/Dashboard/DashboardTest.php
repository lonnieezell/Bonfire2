<?php

namespace Tests\Dashboard;

use Bonfire\Users\User;
use CodeIgniter\I18n\Time;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DashboardTest extends TestCase
{
    protected $refresh = true;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser(['first_name' => 'Ada']);
        $this->user->addGroup('superadmin');
        helper('setting');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    public function testCanSeeDashboard()
    {
        $response = $this->actingAs($this->user)
            ->get(ADMIN_AREA);

        $response->assertOK();
        $response->assertSee('Quick Links');
    }

    #[DataProvider('provideGreetsTheUserByTimeOfDay')]
    public function testGreetsTheUserByTimeOfDay(string $now, string $greeting): void
    {
        Time::setTestNow($now);

        $response = $this->actingAs($this->user)->get(ADMIN_AREA);

        $response->assertSee($greeting . ', Ada', 'h1');
        $response->assertSee('Thursday, October 8');
    }

    public static function provideGreetsTheUserByTimeOfDay(): iterable
    {
        return [
            'morning'   => ['2026-10-08 09:00:00', 'Good morning'],
            'afternoon' => ['2026-10-08 15:00:00', 'Good afternoon'],
            'evening'   => ['2026-10-08 20:00:00', 'Good evening'],
        ];
    }

    public function testQuickLinksAreButtonsInThePageHead(): void
    {
        $html = $this->actingAs($this->user)->get(ADMIN_AREA)->response()->getBody();

        $this->assertMatchesRegularExpression(
            '#<div class="bf-page-head-actions">.*<a class="btn btn-outline-secondary" href="' . preg_quote(url_to('user-list'), '#') . '">#s',
            $html,
        );
    }
}
