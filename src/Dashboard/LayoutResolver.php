<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard;

use Bonfire\Dashboard\Widgets\CellWidget;
use LogicException;

/**
 * Decides which widgets the viewer's dashboard shows, and in what order.
 *
 * A user's own Dashboard.layout comes first. Otherwise the default layout
 * is the site's Dashboard.layout; without one, it is one untitled section
 * holding the plain cells of Dashboard.cells in order.
 *
 * It is also the only place that saves and resets those layouts.
 */
class LayoutResolver
{
    public function __construct(private readonly WidgetRegistry $registry)
    {
    }

    public function resolve(): Layout
    {
        $stored = $this->stored();

        return $stored === null ? $this->defaultLayout() : (new LayoutValidator($this->registry))->validate($stored);
    }

    /**
     * The layout of a user who has not saved their own.
     */
    public function defaultLayout(): Layout
    {
        $site = $this->site();

        return $site === null ? $this->derived() : (new LayoutValidator($this->registry))->validate($site);
    }

    /**
     * Stores the layout as the signed-in user's own. The default layout is
     * not stored, so they keep following the default.
     */
    public function saveForUser(Layout $layout): void
    {
        // A null context is the site's own, so it must not stand in for a user
        $context = self::userContext() ?? throw new LogicException('Only a signed-in user has a layout of their own.');

        if ($layout->toArray() === $this->defaultLayout()->toArray()) {
            setting()->forget('Dashboard.layout', $context);
        } else {
            setting()->set('Dashboard.layout', $layout->toArray(), $context);
        }
    }

    /**
     * Stores the layout as the site default, which every user who has not
     * saved their own sees. The saver follows it too, so they see what they saved.
     */
    public function saveAsSiteDefault(Layout $layout): void
    {
        setting()->set('Dashboard.layout', $layout->toArray());

        $context = self::userContext();

        if ($context !== null) {
            setting()->forget('Dashboard.layout', $context);
        }
    }

    /**
     * Forgets the stored site default, so users who have not saved their
     * own see the configured Dashboard::$layout again.
     */
    public function resetSiteDefault(): void
    {
        setting()->forget('Dashboard.layout');
    }

    private function derived(): Layout
    {
        $available  = $this->registry->available();
        $placements = [];

        foreach ($this->cells() as $alias) {
            $widget = $available[CellWidget::idFor($alias)] ?? null;

            if ($widget !== null) {
                $placements[] = new Placement($widget->id(), $widget->sizes()->default);
            }
        }

        return new Layout([new Section('', $placements)]);
    }

    /**
     * The settings context of the signed-in user's own layout.
     */
    public static function userContext(): ?string
    {
        $userId = auth()->id();

        return $userId === null ? null : 'user:' . $userId;
    }

    /**
     * The viewer's own layout as stored, if they saved one that still reads.
     * One that does not falls back to the default.
     */
    private function stored(): ?array
    {
        $context = self::userContext();
        $layout  = $context === null ? null : setting()->get('Dashboard.layout', $context);

        return LayoutValidator::wellFormed($layout) ? $layout : null;
    }

    /**
     * The site's default layout as stored or configured, if it reads.
     */
    private function site(): ?array
    {
        $layout = setting('Dashboard.layout');

        return LayoutValidator::wellFormed($layout) ? $layout : null;
    }

    /**
     * @return list<string>
     */
    private function cells(): array
    {
        return setting('Dashboard.cells') ?? [];
    }
}
