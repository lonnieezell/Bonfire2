# Dashboard Widgets

Anything an admin can place on the dashboard is a widget. Stats, charts, Log activity and the cells in
`Dashboard.cells` become widgets on their own. For full control, implement `Bonfire\Dashboard\DashboardWidget` and
register it from your module's `initAdmin()`. Bonfire's own Quick Links is built this way
(`Bonfire\Dashboard\Widgets\QuickLinksWidget`).

## Writing a widget

```php
use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;

class OpenOrders implements DashboardWidget
{
    public function id(): string { return 'shop:openOrders'; }
    public function title(): string { return 'Open orders'; }
    public function icon(): string { return 'fas fa-cart-shopping'; }
    public function group(): string { return 'stats'; }
    public function description(): string { return 'Orders waiting to ship'; }
    public function sizes(): SizeRange { return new SizeRange(Size::Half, Size::Quarter, Size::TwoThirds); }
    public function permission(): ?string { return 'shop.orders'; }
    public function settingsUrl(): ?string { return url_to('shop-dashboard-settings'); }

    public function render(): string
    {
        return view('Shop\Views\open_orders', ['orders' => model(OrderModel::class)->open()]);
    }
}
```

- **id** is stored in admins' saved layouts, so keep it unique and never change it. Prefix it with your module's name.
- **group** is the library heading it is listed under: `quickLinks`, `stats`, `charts` or `custom`.
- **sizes** is the width it starts at, then the smallest and largest an admin may pick, from `Size::Quarter`,
  `Third`, `Half`, `TwoThirds` and `Full`.
- **permission** hides the widget from admins who lack it, in the library and on the dashboard, and drops it from
  layouts they save. `null` shows it to every admin.
- **settingsUrl** is where the viewer changes the widget's settings, linked from its tools in edit mode. Return
  `null` when it has none, or the viewer may not change them.
- **render** is called only when the widget is on the viewer's dashboard, so it may run queries.

## Registering it

```php
public function initAdmin()
{
    service('dashboard')->register(new OpenOrders());
}
```

A registered widget is not part of the default layout: admins add it from the widget library, or a site admin puts it
on the [site default](../modules/customizing_the_dashboard.md#the-site-default). To ship it on the default layout,
add its id to the `$layout` of your `Config\Dashboard`.

## From the older APIs

The older ways of adding to the dashboard still work. Each becomes a widget with an id made from its kind and its own
id:

| You have | It becomes | Id | Notes |
| --- | --- | --- | --- |
| A `StatsItem` added to `service('widgets')->widget('stats')` | A stat card, group `stats` | `stats:` + its `id` | Pass `'permission' => '…'` to hide it from admins who lack it. |
| A `ChartsItem` added to `service('widgets')->widget('charts')` | A chart, group `charts` | `charts:` + its `id` | Its `cssClass` sets its default size. Takes `'permission'` too. |
| A view cell listed in `Dashboard.cells` | A full-width widget, group `custom` | `cell:` + its `Class::method` | No permission. Write a widget for more control. |

A stats or chart item fetches its data (`addValue()`, `addValueByFreeQuery()`, `addDataset()`) only when it is on the
viewer's dashboard and the viewer holds its permission. Code that builds a value by hand can ask the same question:

```php
if (service('dashboardContext')->shows(new ItemSettings(WidgetKind::Stats, 'openOrders'), 'shop.orders')) {
    // run the query
}
```

To move one of these to a widget of your own, give the new widget the id it had (for example
`stats:openOrders`), so saved layouts keep it in place, and stop adding the old item. Quick Links did the same: it
keeps the id `cell:Bonfire\Dashboard\DashboardCells::quickLinks`, so installs whose `Dashboard.cells` still lists that
cell show it once, where it was.
