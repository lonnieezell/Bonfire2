# Widgets

Widgets are elements that display information on the dashboard. They are either a small informational card, or
a chart of some sort.

## Configuration

Some settings are available in the administration settings area. Each type of widget has its own specific settings,
which can be used to specify which information is displayed for the widget, as well as whether it's animated, where
labels are displayed, etc.

---

## Your own dashboard widget

Anything an admin can place on the dashboard is a widget. Stats, charts, Log activity and the cells in
`Dashboard.cells` become widgets on their own; for full control, implement `Bonfire\Dashboard\DashboardWidget` and
register it from your module's `initAdmin()`:

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

```php
public function initAdmin()
{
    service('dashboard')->register(new OpenOrders());
}
```

- **id** is stored in admins' saved layouts, so keep it unique and never change it. Prefix it with your module's name.
- **group** is the palette heading it is listed under: `quickLinks`, `stats`, `charts` or `custom`.
- **sizes** is the width it starts at and the smallest and largest an admin may pick, from `Size::Quarter`,
  `Third`, `Half`, `TwoThirds` and `Full`.
- **permission** hides the widget from admins who lack it, in the palette and on the dashboard. `null` shows it to
  every admin.
- **settingsUrl** is where the viewer changes the widget's settings, linked from its tools in edit mode. Return
  `null` when it has none, or the viewer may not change them.
- **render** is called only when the widget is on the viewer's dashboard, so it may run queries.

A registered widget is not part of the default layout: admins add it to their dashboard from the palette.

---

## Statistic widget

The first widget is the statistics widget, which is just a counter with a shortcut that allows me to open the
list of elements of a module. It could be used to display a year-to-day revenue at a glance, total number of users
in the system, new users this month, or any other simple statistic.

Adding a new widget is done by editing the `initAdmin` method of your `Module.php` file, similar to adding a menu:

```php
// Settings widgets stats on dashboard
$widgets = service('widgets');
$users = new UserModel();
$statsItem = new StatsItem([
    'bgColor' => 'bg-blue',
    'title'   => 'Users',
    'value'   => $users->countAll(),
    'id'      => 'countAllUsers643',
    'url'     => ADMIN_AREA . '/users',
    'faIcon'  => 'fa fa-user',
]);
$widgets->widget("stats")->collection('stats')->addItem($statsItem);
```

**bgColor**

bgColor represents the background color of the widget.
The available options are:

```css
- bg-blue
- bg-red
- bg-orange
- bg-light
- bg-dark
- bg-inverse
- bg-indigo
- bg-purple
- bg-pink
- bg-yellow
- bg-green
- bg-teal
- bg-lime
- bg-cyan
- bg-white
- bg-gray
- bg-gray-dark
```

**title**

The title of the widget displayed at the top left

**value**

The value of the widget displayed under the title: typically the total number of records.

However, more elaborate data can be passed as a string, like an html table with tabulated data. See how that is done in `src/Users/Module.php` with a private method `buildTableUsersByGroup()`. **value** can also be passed to the stats item by a standard `addValue()` method, see the same file for an example.

**id**

A uniwue widget ID throughout application (make it descriptive and add extra symbols to make sure the uniqueness).

**url**

The address that displays the list of objects on your module. It will be associated with the word "view detail"

**faIcon**

The FontAwesome icon

### Fetching data only when it is shown

`addValue()` and `addValueByFreeQuery()` only run their query when the dashboard is being displayed and the widget is
on the viewer's dashboard: in their layout and not switched off in the widgets settings. When you build a value yourself, as `buildTableUsersByGroup()` does, ask the same
question before doing the work:

```php
use Bonfire\Widgets\ItemSettings;
use Bonfire\Widgets\WidgetKind;

if (! service('dashboardContext')->shows(new ItemSettings(WidgetKind::Stats, 'countAllUsers643'))) {
    return '';
}
```

In your own tests you can make that answer yes or no without faking the URL by injecting a subclass of
`Bonfire\Widgets\DashboardContext` that overrides `isDashboard()`:

```php
use Bonfire\Widgets\DashboardContext;
use Config\Services;

Services::injectMock('dashboardContext', new class () extends DashboardContext {
    public function isDashboard(): bool
    {
        return true;
    }
});
```

## Configuration

All settings are available in the `Bonfire\Widgets\Config\Stats` class, or in the admin settings area.

In the administration settings area, you can also choose to:
- Display the "View Detail" link

---

## Charts widget

Adding a new chart widget is done in the `initAdmin` method of your `Module.php` file, similar to adding a menu:

```php
$statsItem = new ChartsItem([
    'title'    => 'User classification by group',
    'type'     => 'line',
    'id'       => 'usersByGroupLine123',
    'cssClass' => 'col-6',
]);
$statsItem->addDataset('auth_groups_users', 'group', 'user_id');
$widgets->widget('charts')->collection('charts')->addItem($statsItem);
```

**title**

The title of the chart displayed at the top center

**type**

The type of chart.
The available options are:
```
- line
- bar
- doughnut
- pie
- polarArea
```

**id**

A uniwue widget ID throughout application (make it descriptive and add extra symbols to make sure the uniqueness).

**cssClass**

Set the width of the chart.
Examples options are:
```
- col-3
- col-6
- col-9
- col-12
```

**addDataset(string $tableName, string $groupField, string $countField, string $selectMode = 'count')**

Currently, only one dataset can be added to the chart. The required parameters are:

- Name of the table
- Grouping field
- Field to count

It is possible to indicate a fourth parameter to choose the counting mode; the possible values are:

```php
- count
- avg
- max
- min
- sum
```

- if indicate **count** then **selectCount($field)** is executed
- if indicate **avg** then **selectAvg($field)**  is executed
- if indicate **max** then **selectMax($field)**  is executed
- if indicate **min** then **selectMin($field)**  is executed
- if indicate **sum** then **selectSum($field)**  is executed

---

## Log activity widget

The Log activity widget charts the newest log files, one stacked bar per file, split into Error, Warning, Info and
Debug entries. CRITICAL, ALERT and EMERGENCY entries count as Error, and NOTICE entries count as Info. Each bar links
to that log file, and the headline counts the errors across the files shown.

It is built in: turn it on in the widgets settings like any other widget. Only users with the `logs.view` permission
see it, and the log files are only read when it is on and the dashboard is shown. It is displayed by the
`Bonfire\Widgets\Cells\WidgetCells::logs` cell.

---

## Configuration

All settings are available in the administration settings area.

Each type of widget has its own specific settings.

---

### Charts ###

***Line Chart***

You can set the default values in the `Bonfire\Widgets\Config\LineChart` Class

In the administration settings area, you can choose to:

- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- You can choose the value of the tension: Bezier curve tension of the line. Set to 0 to draw straightlines.

Advanced settings:

- You can set Line color
- You can set Line width

---

***Bar Chart***

You can set the default values in the `Bonfire\Widgets\Config\BarChart` Class

In the administration settings area, you can choose to:

- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- Assign a predefined color scheme to fill the chart

---

***Doughnut Chart***

You can set the default values in the `Bonfire\Widgets\Config\DoughnutChart` Class

In the administration settings area, you can choose to:

- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- Assign a predefined color scheme to fill the chart

---

***Pie Chart***

You can set the default values in the `Bonfire\Widgets\Config\PieChart` Class

In the administration settings area, you can choose to:

- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- Assign a predefined color scheme to fill the chart

---

***Polar Area Chart***

You can set the default values in the `Bonfire\Widgets\Config\PolarAreaChart` Class

In the administration settings area, you can choose to:

- Display the title
- View the legend
- Choose the location of the legend
- Enable animation
- Assign a predefined color scheme to fill the chart

---

### Log activity ###

You can set the default values in the `Bonfire\Widgets\Config\LogActivity` Class

In the administration settings area, you can choose:

- How many of the newest log files to show, from 1 to 31 (12 by default). Every file shown is read on each dashboard
  load.
- To hide Debug entries
