<?php

namespace Tests\Recycler;

use Bonfire\Dashboard\WidgetValues;
use Bonfire\Recycler\Libraries\Recycler;
use Bonfire\Recycler\Widgets\RecyclerSummaryWidget;
use Bonfire\Users\Models\UserModel;
use Config\Services;
use Tests\Support\Models\RecyclerHooksModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class RecyclerSummaryWidgetTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        Services::injectMock('recycler', new Recycler());
        service('recycler')
            ->register('users', 'Users', UserModel::class, ['username'])
            ->register('hooks', 'Hooks', RecyclerHooksModel::class, ['username']);
    }

    protected function tearDown(): void
    {
        Services::resetSingle('recycler');

        parent::tearDown();
    }

    public function testListsEachResourceWithDeletedRowsAndLinksToIt(): void
    {
        $hidden = $this->createUser(['username' => 'hidden']);
        $other  = $this->createUser();
        $model  = new UserModel();
        $model->delete($hidden->id);
        $model->delete($other->id);

        $html = $this->render();

        $this->assertSame(2, substr_count($html, '<li'));
        $this->assertMatchesRegularExpression($this->row('users', 2), $html);
        // The hooks resource hides the "hidden" user, as the Recycler's own list does
        $this->assertMatchesRegularExpression($this->row('hooks', 1), $html);
    }

    public function testHidesResourcesWithNothingDeleted(): void
    {
        // Deleted, but the hooks resource hides it, leaving that resource empty
        $model = new UserModel();
        $model->delete($this->createUser(['username' => 'hidden'])->id);

        $html = $this->render();

        $this->assertSame(1, substr_count($html, '<li'));
        $this->assertStringNotContainsString('?r=hooks', $html);
    }

    public function testShowsAnEmptyStateWhenNothingIsDeleted(): void
    {
        $this->createUser();

        $html = $this->render();

        $this->assertStringContainsString('bf-rows-empty', $html);
        $this->assertStringNotContainsString('<li', $html);
    }

    public function testNeedsTheRecyclerViewPermission(): void
    {
        $widget = new RecyclerSummaryWidget();

        $this->assertSame('recycler:summary', $widget->id());
        $this->assertSame('recycler.view', $widget->permission());
    }

    /**
     * A pattern for one row: the link to the resource's recycler view, then its count before the row ends.
     */
    private function row(string $alias, int $count): string
    {
        return '#href="' . preg_quote(url_to('recycler') . '?r=' . $alias, '#') . '"(?:(?!</li>).)*>' . $count . '<#s';
    }

    private function render(): string
    {
        $widget = new RecyclerSummaryWidget();

        return $widget->render(WidgetValues::resolve($widget->fields()));
    }
}
