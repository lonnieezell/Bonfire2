# Dashboard Widgets

Anything an admin can place on the [dashboard](../modules/dashboard.md) is a widget. A module adds widgets from its
`initAdmin()` in one of two ways:

- [Write a widget](#writing-a-widget): implement `Bonfire\Dashboard\DashboardWidget`. You choose its title, icon,
  sizes, settings and markup. Bonfire's own widgets, such as Quick Links (`Bonfire\Dashboard\Widgets\QuickLinksWidget`),
  are built this way.
- [Add a stat card or chart](#stat-cards-and-charts): describe a number or a chart with a `StatsItem` or
  `ChartsItem`, and Bonfire draws it.

Any widget a module adds is not on the default layout: admins add it from the widget library, or a site admin puts it
on the [site default](../modules/dashboard.md#the-site-default). To ship it on the default layout, add its id to the
`$layout` of your `Config\Dashboard`.

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
            WidgetField::toggle('showTotals', 'Show totals', default: true),
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
  The type keys `quickLinks`, `stats`, `charts` and `custom` still work, each under its own heading.
- **sizes** is the width it starts at, then the smallest and largest an admin may pick, from `Size::Quarter`,
  `Third`, `Half`, `TwoThirds` and `Full`.
- **permission** hides the widget from admins who lack it, in the library and on the dashboard, and drops it from
  layouts they save. `null` shows it to every admin.
- **fields** are the [settings](#settings) admins may change for the widget on their own dashboard. Return `[]` when
  it has none, and its edit-mode tools offer no settings.
- **render** is called only when the widget is on the viewer's dashboard, so it may run queries. It gets the value
  of each field as the viewer placed it.

### Registering it

```php
public function initAdmin()
{
    service('dashboard')->register(new OpenOrders());
}
```

### Settings

Each `WidgetField` is one setting, drawn in the widget's settings panel. Bonfire draws the form, checks what is
posted, stores each admin's values with their layout, and hands `render()` the result. Read a value with
`$values->get('name')`, not with `setting()`.

| Field | Input | Value |
| --- | --- | --- |
| `WidgetField::toggle($name, $label, default: …)` | A switch | `bool` |
| `WidgetField::number($name, $label, default: …, min: …, max: …, step: …)` | A number box | `int`, or `float` when `step` is a fraction; kept within `min` and `max` |
| `WidgetField::choice($name, $label, $options, default: …)` | Segmented buttons, or a select for more than four options | One of the keys of `$options` (value => label) |
| `WidgetField::text($name, $label, default: …)` | A text box | `string`, trimmed, at most 255 characters |

Every field also takes an optional `description:` shown under its label. A placed widget uses each field's `default`
until an admin changes it. Only values that differ from the default are stored, so changing the default in code later
reaches every admin who left the field alone. A value that is not of the field's type, a choice the field does not
offer, or the name of a field the widget no longer declares is dropped when the layout is saved, and ignored when it
is rendered.

## Stat cards and charts

For a single number or a chart of one table, add an item to `service('widgets')` instead of writing a class. Each
item becomes a widget with the id `stats:` or `charts:` plus its own `id`.

Give an item its value with `addValue()`, `addValueByFreeQuery()` or `addDataset()`. They store the query and run it
only when the dashboard shows the widget, to a viewer who holds its permission. A `value` passed to the constructor
is built every time your module boots, on every admin page.

### Stat cards

```php
use Bonfire\Widgets\Types\Stats\StatsItem;

public function initAdmin()
{
    $item = new StatsItem([
        'id'         => 'openOrders',
        'title'      => 'Open orders',
        'url'        => ADMIN_AREA . '/orders',
        'faIcon'     => 'fas fa-cart-shopping',
        'permission' => 'shop.orders',
        'module'     => 'Shop',
    ]);
    $item->addValue('orders', "status = 'open'");

    service('widgets')->widget('stats')->collection('stats')->addItem($item);
}
```

| Key | Meaning |
| --- | --- |
| `id` | Unique across the install, and stored in saved layouts: the widget's id is `stats:` plus this. Never change it. |
| `title` | Shown at the top of the card. |
| `url` | The page the card's "View Detail" link opens. |
| `faIcon` | FontAwesome classes for the icon next to the title. |
| `permission` | Hides the card from admins who lack it, like a widget's `permission()`. |
| `module` | The library heading it is listed under. Without it, it is listed under Stats. |
| `value` | A value built now, on every admin page. The card prints it unescaped, so it may be HTML. Prefer the methods below. |
| `bgColor` | Accepted, but no longer changes the card. |

A stat card is ¼ wide and has one setting, showing the "View Detail" link.

**`addValue(string $tableName, ?string $whereString = null, string $selectMode = 'count')`** counts the rows of
`$tableName` whose `deleted_at` is null, so the table needs a `deleted_at` column. `$whereString` adds a condition.
`$selectMode` `avg`, `max`, `min` or `sum` takes that of the table's `value` column in place of counting.

**`addValueByFreeQuery(string $query)`** runs `$query` and shows the first column of its first row.

### Charts

```php
use Bonfire\Widgets\Types\Charts\ChartsItem;

$item = new ChartsItem([
    'id'         => 'ordersByStatus',
    'title'      => 'Orders by status',
    'type'       => 'doughnut',
    'cssClass'   => 'col-6',
    'permission' => 'shop.orders',
    'module'     => 'Shop',
]);
$item->addDataset('orders', 'status', 'id');

service('widgets')->widget('charts')->collection('charts')->addItem($item);
```

| Key | Meaning |
| --- | --- |
| `id`, `title`, `permission`, `module` | As for a stat card, with `charts:` in place of `stats:`. Listed under Charts without `module`. |
| `type` | `line`, `bar`, `doughnut`, `pie` or `polarArea`. |
| `cssClass` | Its default width: `col-3`, `col-4`, `col-6`, `col-8` or `col-12`. Anything else starts at half width. |

**`addDataset(string $tableName, string $groupField, string $countField, string $selectMode = 'count')`** groups the
rows of `$tableName` by `$groupField`, one label each, and counts `$countField` in each group. `$selectMode` `avg`,
`max`, `min` or `sum` takes that of `$countField` in place of counting. A chart has one dataset.

Each chart has the settings listed in [Widget settings](../modules/dashboard.md#widget-settings).

## Legacy: view cells

Before widgets, the dashboard showed the [view cells](https://codeigniter.com/user_guide/outgoing/view_cells.html)
listed in the `$cells` property of the Dashboard config file. That still works, but a cell gets no title, icon,
sizes, settings or permission of its own: write a [widget](#writing-a-widget) instead.

```php
public array $cells = [
    'Bonfire\Widgets\Cells\WidgetCells::stats',
    'Bonfire\Widgets\Cells\WidgetCells::logs',
    'Bonfire\Widgets\Cells\WidgetCells::charts',
];
```

A cell is a class method that returns HTML:

```php
class ShopCells
{
    public function openOrders()
    {
        return view('Shop\Views\open_orders', [
            'orders' => model(OrderModel::class)->open(),
        ]);
    }
}
```

Add `'Shop\Cells\ShopCells::openOrders'` to `$cells` and it becomes a full-width widget with the id
`cell:Shop\Cells\ShopCells::openOrders`, listed under Custom, with no permission. The `stats`, `logs` and `charts`
cells listed by default don't become widgets: they stand for the stat cards and charts above. When the config's
`$layout` is `null`, the default layout is the cells of `$cells`, in order.

### Moving to a widget

To replace a stat card, chart or cell with a widget of your own, give the new widget the id the old one had (for
example `stats:openOrders` or `cell:Shop\Cells\ShopCells::openOrders`), so saved layouts keep it in place, and stop
adding the old item. Quick Links did the same: it keeps the id `cell:Bonfire\Dashboard\DashboardCells::quickLinks`,
so installs whose `$cells` still lists that cell show it once, where it was.
