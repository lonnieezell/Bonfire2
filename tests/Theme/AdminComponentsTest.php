<?php

namespace Tests\Theme;

use Bonfire\View\ComponentRenderer;
use Bonfire\View\Theme;
use Tests\Support\TestCase;

/**
 * The Admin theme's shared view components.
 *
 * @internal
 */
final class AdminComponentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Theme::setTheme('Admin');
    }

    protected function tearDown(): void
    {
        Theme::setTheme('');

        parent::tearDown();
    }

    public function testPageHeadShowsTitleCountSubtitleBackLinkAndActions(): void
    {
        $html = $this->render(
            '<x-page-head title="Users" count="12" subtitle="Everyone with an account" back="/admin" backLabel="Home">'
            . '<a href="/new">New</a></x-page-head>',
        );

        $this->assertStringContainsString('<h1>Users</h1>', $html);
        $this->assertStringContainsString('<span class="bf-count">12</span>', $html);
        $this->assertStringContainsString('Everyone with an account', $html);
        $this->assertMatchesRegularExpression('#<a class="bf-back" href="/admin">.*Home</a>#s', $html);
        $this->assertStringContainsString('<div class="bf-page-head-actions"><a href="/new">New</a></div>', $html);
    }

    public function testPageHeadStillWrapsLegacyContent(): void
    {
        $html = $this->render('<x-page-head><h2>Settings</h2></x-page-head>');

        $this->assertMatchesRegularExpression('#<div class="page-head bf-page-head">\s*<h2>Settings</h2>\s*</div>#', $html);
    }

    public function testPageHeadEscapesItsAttributes(): void
    {
        $html = $this->render('<x-page-head title="<b>Users</b>" back=\'/x" onclick="alert(1)\' />');

        $this->assertStringContainsString('<h1>&lt;b&gt;Users&lt;/b&gt;</h1>', $html);
        $this->assertStringNotContainsString('" onclick="', $html);
    }

    public function testAdminBoxShowsAnOptionalHeader(): void
    {
        $plain  = $this->render('<x-admin-box><p>Body</p></x-admin-box>');
        $titled = $this->render('<x-admin-box title="Site" description="a < b & c"><p>Body</p></x-admin-box>');

        $this->assertStringNotContainsString('bf-card-head', $plain);
        $this->assertStringContainsString('<p>Body</p>', $plain);
        $this->assertStringContainsString('<h2>Site</h2>', $titled);
        $this->assertStringContainsString('<p>a &lt; b &amp; c</p>', $titled);
    }

    public function testSettingRowLabelsItsControl(): void
    {
        $html = $this->render(
            '<x-setting-row label="Site name" description="Shown in the admin." for="siteName">'
            . '<input id="siteName"></x-setting-row>',
        );

        $this->assertStringContainsString('<label class="bf-setting-label" for="siteName">Site name</label>', $html);
        $this->assertStringContainsString('<div class="bf-setting-desc">Shown in the admin.</div>', $html);
        $this->assertStringContainsString('<div class="bf-setting-control"><input id="siteName"></div>', $html);
    }

    public function testSwitchPostsZeroWhenUnchecked(): void
    {
        $html = $this->render('<x-switch name="active" label="Activated" />');

        $this->assertMatchesRegularExpression(
            '#<input type="hidden" name="active" value="0">\s*<input type="checkbox" role="switch" class="bf-switch-input" name="active" value="1" aria-label="Activated"\s*>#',
            $html,
        );
    }

    public function testSwitchCanStartCheckedAndPassesOtherAttributesThrough(): void
    {
        $html = $this->render('<x-switch name="banned" id="banned" checked="1" x-model="isBanned" label=\'Say "hi" now\' />');

        $this->assertMatchesRegularExpression('#<input type="checkbox"[^>]* checked[ >]#', $html);
        $this->assertStringContainsString('id="banned"', $html);
        $this->assertStringContainsString('x-model="isBanned"', $html);
        $this->assertStringContainsString('aria-label="Say &quot;hi&quot; now"', $html);
    }

    public function testDisabledSwitchDoesNotPostZero(): void
    {
        $html = $this->render('<x-switch name="active" disabled />');

        $this->assertMatchesRegularExpression('#<input type="hidden" name="active" value="0" disabled>#', $html);
        $this->assertMatchesRegularExpression('#<input type="checkbox"[^>]* disabled=""#', $html);
    }

    public function testSwitchInAnArrayPostsNothingWhenOff(): void
    {
        $html = $this->render('<x-switch name="permissions[]" value="users.edit" />');

        $this->assertStringNotContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="permissions[]" value="users.edit"', $html);
    }

    public function testSwitchStaysUncheckedForAnEmptyCheckedAttribute(): void
    {
        $this->assertStringNotContainsString(' checked', $this->render('<x-switch name="a" checked="" />'));
    }

    public function testSegmentedRendersOneRadioPerOption(): void
    {
        $html = $this->render(
            '<x-segmented name="timeFormat" value="H:i" label="Time format" options=\'{"g:i A":"12-hour","H:i":"<24-hour>"}\' />',
        );

        $this->assertStringContainsString('role="radiogroup" aria-label="Time format"', $html);
        $this->assertMatchesRegularExpression('#<input type="radio" name="timeFormat" value="g:i A"\s*>\s*<span>12-hour</span>#', $html);
        $this->assertMatchesRegularExpression('#<input type="radio" name="timeFormat" value="H:i" checked>\s*<span>&lt;24-hour&gt;</span>#', $html);
    }

    public function testChipLinksWithACountAndMarksTheActiveOne(): void
    {
        $html = $this->render('<x-chip label="Admins" count="3" href="/admin/users?group=admin" active="1" hx-get="/admin/users?group=admin" />');

        $this->assertMatchesRegularExpression(
            '#<a class="bf-chip active" href="/admin/users\?group=admin" aria-current="true" hx-get="/admin/users\?group=admin">\s*Admins\s*<span class="bf-chip-count">3</span>\s*</a>#',
            $html,
        );
    }

    public function testChipWithoutALinkIsAToggleButton(): void
    {
        $html = $this->render('<x-chip label="<All>" />');

        $this->assertMatchesRegularExpression('#<button type="button" class="bf-chip" aria-pressed="false">\s*&lt;All&gt;#', $html);
        $this->assertStringNotContainsString('bf-chip-count', $html);
    }

    public function testChipWithANameIsARadioInAForm(): void
    {
        $plain   = $this->render('<x-chip name="role" value="user" label="User" count="2" />');
        $checked = $this->render('<x-chip name="role" value="" label="All" active="1" />');

        $this->assertMatchesRegularExpression('#<label class="bf-chip">\s*<input type="radio" name="role" value="user"\s*>\s*User\s*<span class="bf-chip-count">2</span>\s*</label>#', $plain);
        $this->assertMatchesRegularExpression('#<input type="radio" name="role" value="" checked>#', $checked);
    }

    public function testStatusShowsADotInItsTone(): void
    {
        $this->assertMatchesRegularExpression(
            '#<span class="bf-status bf-status-ok"><span class="bf-dot" aria-hidden="true"></span>Active</span>#',
            $this->render('<x-status tone="ok" label="Active" />'),
        );
    }

    public function testStatusFallsBackToMutedForAnUnknownTone(): void
    {
        $html = $this->render('<x-status tone=\'x" onclick="y\' label="<b>Banned</b>" />');

        $this->assertStringContainsString('class="bf-status bf-status-muted"', $html);
        $this->assertStringContainsString('&lt;b&gt;Banned&lt;/b&gt;', $html);
    }

    public function testEmptyStateShowsTitleAndHint(): void
    {
        $html = $this->render('<x-empty-state title="No users match" hint="Try a different search & group." />');

        $this->assertMatchesRegularExpression(
            '#<div class="bf-empty">\s*<div class="bf-empty-title">No users match</div>\s*<div class="bf-empty-hint">Try a different search &amp; group.</div>#',
            $html,
        );
    }

    public function testTabsWrapTheirItemsInUnderlineTabs(): void
    {
        $html = $this->render('<x-tabs><li class="nav-item"><a class="nav-link active" href="#">Details</a></li></x-tabs>');

        $this->assertMatchesRegularExpression(
            '#<ul class="nav bf-tabs">\s*<li class="nav-item"><a class="nav-link active" href="\#">Details</a></li>\s*</ul>#',
            $html,
        );
    }

    public function testSelectionBarCountsSelectedRowsAndHoldsTheBulkActions(): void
    {
        $html = $this->render('<x-selection-bar><button type="submit">Delete</button></x-selection-bar>');

        $this->assertStringContainsString('x-data="bfSelectionBar"', $html);
        $this->assertStringContainsString('x-show="count > 0"', $html);
        $this->assertStringContainsString('data-label="%s selected"', $html);
        $this->assertStringContainsString('<button type="submit">Delete</button>', $html);
        $this->assertStringContainsString('aria-label="Clear selection"', $html);
    }

    public function testSaveBarDiscardsOrSubmitsTheForm(): void
    {
        $html = $this->render('<x-save-bar save="Save & close" />');

        $this->assertStringContainsString('x-data="bfSaveBar"', $html);
        $this->assertStringContainsString('Unsaved changes', $html);
        $this->assertMatchesRegularExpression('#<button type="button"[^>]*@click="discard\(\)"[^>]*>\s*Discard\s*</button>#', $html);
        $this->assertMatchesRegularExpression('#<button type="submit" class="btn btn-primary">\s*Save &amp; close\s*</button>#', $html);
    }

    public function testButtonKeepsItsAttributes(): void
    {
        $html = $this->render('<x-button color="danger" type="button" id="del" name="delete" onclick="return confirm(\'Sure?\')">Delete</x-button>');

        $this->assertStringContainsString('class="btn btn-danger"', $html);
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('id="del"', $html);
        $this->assertStringContainsString('name="delete"', $html);
        $this->assertStringContainsString('onclick="return confirm(&#039;Sure?&#039;)"', $html);
        $this->assertStringContainsString('Delete', $html);
    }

    public function testButtonDefaultsToAPrimarySubmitAndEscapesItsColor(): void
    {
        $this->assertMatchesRegularExpression('#<button\s+type="submit"\s+class="btn btn-primary"\s*>\s*Save\s*</button>#', $this->render('<x-button>Save</x-button>'));
        $this->assertStringNotContainsString('" onclick="', $this->render('<x-button color=\'x" onclick="y\'>Go</x-button>'));
    }

    public function testLegacyComponentsStillRenderTheirSlots(): void
    {
        $this->assertStringContainsString('Users', $this->render('<x-module-title>Users</x-module-title>'));
        $this->assertStringContainsString('<p>x</p>', $this->render('<x-button-container><p>x</p></x-button-container>'));
        $this->assertStringContainsString('x-on:click="filtered = ! filtered"', $this->render('<x-filter-link />'));
    }

    private function render(string $input): string
    {
        return (new ComponentRenderer())->render($input);
    }
}
