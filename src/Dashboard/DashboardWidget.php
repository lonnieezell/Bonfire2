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

/**
 * Anything that can be placed on the admin dashboard.
 *
 * Modules register their own with service('dashboard')->register()
 * from initAdmin(). render() is only called for widgets in the
 * viewer's resolved layout, so it may run expensive queries.
 */
interface DashboardWidget
{
    /**
     * Unique and stable: it is stored in users' saved layouts.
     */
    public function id(): string;

    public function title(): string;

    /**
     * FontAwesome classes, e.g. "fas fa-users".
     */
    public function icon(): string;

    /**
     * The palette group: quickLinks, stats, charts or custom.
     */
    public function group(): string;

    public function description(): string;

    public function sizes(): SizeRange;

    /**
     * The permission a user needs to see the widget, if any.
     */
    public function permission(): ?string;

    /**
     * The settings admins may change for the widget as placed on their
     * dashboard. With none, its edit-mode tools offer no settings.
     *
     * @return list<WidgetField>
     */
    public function fields(): array;

    /**
     * @param WidgetValues $values Each field's value as placed
     */
    public function render(WidgetValues $values): string;
}
