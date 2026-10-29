<?php

namespace Tests\Users;

use Bonfire\Dashboard\WidgetValues;
use Bonfire\Users\Widgets\FailedLoginsWidget;
use CodeIgniter\Shield\Models\LoginModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class FailedLoginsWidgetTest extends TestCase
{
    protected $refresh = true;
    private FailedLoginsWidget $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->widget = new FailedLoginsWidget();
    }

    public function testListsOnlyFailedLoginsNewestFirst(): void
    {
        $oldest = $this->recordLogin('old@example.com', false);
        $middle = $this->recordLogin('mid@example.com', false);
        $this->recordLogin('ok@example.com', true);
        $newest = $this->recordLogin('new@example.com', false);

        $this->assertSame([$newest, $middle], array_column($this->widget->logins(2), 'id'));
        $this->assertSame([$newest, $middle, $oldest], array_column($this->widget->logins(5), 'id'));
    }

    public function testShowsAsManyLoginsAsSetLinkingKnownUsersToTheirSecurityTab(): void
    {
        $user = $this->createUser();
        $this->actingAsUserEditor();
        $this->recordLogin($user->email, false);
        $this->recordLogin('stranger@example.com', false);
        $this->recordLogin(strtoupper($user->email), false);

        $html = $this->renderWithCount(2);

        $this->assertSame(2, substr_count($html, '<li'));
        $this->assertSame(1, substr_count($html, '<a '));
        $this->assertSame(1, substr_count($html, 'href="' . $user->adminLink('security') . '"'));
    }

    public function testDoesNotLinkAnAttemptOfAnotherTypeThatHappensToMatchAnEmail(): void
    {
        $user = $this->createUser();
        $this->actingAsUserEditor();
        $this->recordLogin($user->email, false, 'username');

        $this->assertStringNotContainsString('<a ', $this->renderWithCount(5));
    }

    public function testDoesNotLinkWhenTheViewerMayNotOpenTheSecurityTab(): void
    {
        $user   = $this->createUser();
        $viewer = $this->createUser();
        $viewer->addPermission('users.view');
        $this->actingAs($viewer);
        $this->recordLogin($user->email, false);

        $html = $this->renderWithCount(5);

        $this->assertSame(1, substr_count($html, '<li'));
        $this->assertStringNotContainsString('<a ', $html);
    }

    private function actingAsUserEditor(): void
    {
        $editor = $this->createUser();
        $editor->addPermission('users.edit');
        $this->actingAs($editor);
    }

    private function renderWithCount(int $count): string
    {
        return $this->widget->render(WidgetValues::resolve($this->widget->fields(), ['count' => $count]));
    }

    public function testShowsAnEmptyStateWithoutFailedLogins(): void
    {
        $this->recordLogin('ok@example.com', true);

        $html = $this->widget->render(WidgetValues::resolve($this->widget->fields(), []));

        $this->assertStringNotContainsString('<li', $html);
        $this->assertStringContainsString('bf-rows-empty', $html);
    }

    public function testShowsFiveByDefaultAndAtMostTwenty(): void
    {
        $this->assertSame(5, $this->widget->fields()[0]->default);
        $this->assertSame(20, $this->widget->fields()[0]->cast(50));
    }

    private function recordLogin(string $identifier, bool $success, string $idType = 'email_password'): int
    {
        $logins = model(LoginModel::class);
        $logins->recordLoginAttempt($idType, $identifier, $success, '10.0.0.1', 'Curl');

        return $logins->getInsertID();
    }
}
