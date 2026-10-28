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
use Bonfire\Dashboard\WidgetField;
use Bonfire\Dashboard\WidgetValues;

class OpenOrders implements DashboardWidget
{
    public function id(): string { return 'shop:openOrders'; }
    public function title(): string { return 'Open orders'; }
    public function icon(): string { return 'fas fa-cart-shopping'; }
    public function group(): string { return 'Shop'; }
    public function description(): string { return 'Orders waiting to ship'; }
    public function sizes(): SizeRange { return new SizeRange(Size::Half, Size::Quarter, Size::TwoThirds); }
    public function permission(): ?string { return 'shop.orders'; }

    public function fields(): array
    {
        return [
            WidgetField::number('limit', 'Orders to show', default: 5, min: 1, max: 20),
            WidgetField::toggle('showTotals', 'Show totals', default: true, key: 'Shop.dashboardTotals'),
        ];
    }

    public function render(WidgetValues $values): string
    {
        return view('Shop\Views\open_orders', [
            'orders'     => model(OrderModel::class)->open($values->get('limit')),
            'showTotals' => $values->get('showTotals'),
        ]);
    }
}
```

- **id** is stored in admins' saved layouts, so keep it unique and never change it. Prefix it with your module's name.
- **group** is the library heading it is listed under: your module's name. The library lists modules in name order.
  The older type keys `quickLinks`, `stats`, `charts` and `custom` still work, each under its own heading.
- **sizes** is the width it starts at, then the smallest and largest an admin may pick, from `Size::Quarter`,
  `Third`, `Half`, `TwoThirds` and `Full`.
- **permission** hides the widget from admins who lack it, in the library and on the dashboard, and drops it from
  layouts they save. `null` shows it to every admin.
- **fields** are the [settings](#settings) admins may change for the widget on their own dashboard. Return `[]` when
  it has none, and its edit-mode tools offer no settings.
- **render** is called only when the widget is on the viewer's dashboard, so it may run queries. It gets the value
  of each field as the viewer placed it.

## Settings

Each `WidgetField` is one setting, drawn in the widget's settings panel. Bonfire draws the form, checks what is
posted, stores each admin's values with their layout, and hands `render()` the result. Read a value with
`$values->get('name')`, not with `setting()`.

| Field | Input | Value |
| --- | --- | --- |
| `WidgetField::toggle($name, $label, default: …)` | A switch | `bool` |
| `WidgetField::number($name, $label, default: …, min: …, max: …, step: …)` | A number box | `int`, or `float` when `step` is a fraction; kept within `min` and `max` |
| `WidgetField::choice($name, $label, $options, default: …)` | Segmented buttons, or a select for more than four options | One of the keys of `$options` (value => label) |
| `WidgetField::text($name, $label, default: …)` | A text box | `string`, trimmed, at most 255 characters |

Every field also takes an optional `description:` shown under its label, and `key:`, the name of a setting
(`Class.property`) it follows. A field follows that setting's stored value, or its `default` when it has no `key`
or the setting is not stored, until an admin changes it. Fields with a `key` are also the site-wide defaults:
Settings → Widgets has a tab for each `Class`, listing those fields, which saves the setting and **Reset all widget
settings** forgets it. Widgets that share a key, as every chart of one type does, should declare it alike: the tab
shows the first declaration. Only values that differ from it are stored, so changing the setting later reaches every
admin who left the field alone. A value that is not of the field's type, a choice the field does not offer, or the
name of a field the widget no longer declares is dropped when the layout is saved, and ignored when it is rendered.

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
| A `StatsItem` added to `service('widgets')->widget('stats')` | A stat card, listed under `'module' => '…'`, else Stats | `stats:` + its `id` | Pass `'permission' => '…'` to hide it from admins who lack it. |
| A `ChartsItem` added to `service('widgets')->widget('charts')` | A chart, listed under `'module' => '…'`, else Charts | `charts:` + its `id` | Its `cssClass` sets its default size. Takes `'permission'` too. |
| A view cell listed in `Dashboard.cells` | A full-width widget, listed under Custom | `cell:` + its `Class::method` | No permission. Write a widget for more control. |

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
