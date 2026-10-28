<?php

namespace Tests\Support;

use Bonfire\Users\User;
use Config\Services;

/**
 * Renders the admin dashboard for a user and reads its placed widgets.
 */
trait RendersDashboard
{
    /**
     * Forgets the settings and shared services a dashboard request leaves behind,
     * such as the JSON content type a layout save sets on the shared response.
     */
    protected function forgetDashboardState(): void
    {
        foreach (['dashboard', 'dashboardContext', 'viewMeta', 'response'] as $service) {
            Services::resetSingle($service);
        }
        setting()->flush();
    }

    protected function dashboardHtml(User $user): string
    {
        // Rendered charts leave their scripts on the shared page metadata
        Services::resetSingle('viewMeta');

        return (string) $this->actingAs($user)->get(ADMIN_AREA)->response()->getBody();
    }

    /**
     * The page up to the edit-mode templates: the placed tiles, not the palette's placeholders.
     */
    protected function placedHtml(string $html): string
    {
        return substr($html, 0, (int) strpos($html, '<template x-ref="newSection">'));
    }

    /**
     * @return list<string>
     */
    protected function placedIds(User $user): array
    {
        preg_match_all('/data-widget-id="([^"]+)"/', $this->placedHtml($this->dashboardHtml($user)), $matches);

        return array_map(html_entity_decode(...), $matches[1]);
    }
}
