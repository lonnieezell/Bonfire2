# The Dashboard

The Dashboard is the first page you seen when you log into the admin area. It provides a place to put the widgets that provide a quick overview of the site's status, or other information that is important to the user.

## Configuration

By default, the Dashboard displays:

- the quick links;
- a **Users** section with the Users by Group and Users in Recycler stats, each half the width;
- a **Monitoring** section with Log activity.

That default is the `$layout` property of the Dashboard config file, in the same format a saved layout uses. Change
it to give every admin who hasn't customized their dashboard a different one; set it to `null` and the default is
the cells below instead, in order:

```php
public ?array $layout = [
    'version'  => 1,
    'sections' => [
        ['title' => '', 'widgets' => [
            ['id' => 'cell:Bonfire\Dashboard\DashboardCells::quickLinks', 'size' => 'full'],
        ]],
        ['title' => 'Users', 'widgets' => [
            ['id' => 'stats:usersExpTable693', 'size' => 'half'],
            ['id' => 'stats:usersInRecycler693', 'size' => 'half'],
        ]],
        ['title' => 'Monitoring', 'widgets' => [
            ['id' => 'logs:logActivity', 'size' => 'full'],
        ]],
    ],
];
```

Widgets the viewer may not see, or that are switched off in Settings → Widgets, are left out, and so is a section
left empty.

You can create additional [View Cells](https://codeigniter.com/user_guide/outgoing/view_cells.html) and add them to the Dashboard config file to have them displayed on the Dashboard, also. They will be displayed in the order that they are listed in the config file. Each one takes up a full row.

```php
public array $cells = [
    'Bonfire\Dashboard\DashboardCells::quickLinks',
    'Bonfire\Widgets\Cells\WidgetCells::stats',
    'Bonfire\Widgets\Cells\WidgetCells::logs',
    'Bonfire\Widgets\Cells\WidgetCells::charts',
];
```

## Customizing the dashboard

Every admin can arrange their own dashboard without changing anyone else's. Until they do, they see the default
layout above. Other widgets are added from the widget library.

Click **Customize** to enter edit mode. Nothing is stored until you click **Save layout**; **Cancel** puts the
dashboard back the way it was. Every change can be undone and redone with the arrows in the edit bar, or Ctrl+Z
(⌘Z) and Shift+Ctrl+Z, and the message after a removal or deletion has its own **Undo**. In edit mode you can:

- drag a widget to reorder it, also into another section;
- size a widget from the tools above it: ¼, ⅓, ½, ⅔ or Full width (3, 4, 6, 8 or 12 of 12 columns), within the
  range that widget allows, or drag its bottom-right corner;
- open a widget's settings from its sliders button, when it has settings you may change;
- remove a widget with its trash button, and add widgets from **Add widget**, which lists every widget and marks the
  ones already on your dashboard. Click **Add**, or drag one straight onto a section. An added widget shows a
  placeholder until you save;
- give a section an optional heading, and from its ⋯ menu add a widget to it, move it up or down, insert a section
  below it or delete it with its widgets;
- reset to the default layout with the clock button. Saving the default layout forgets yours, so you follow the
  default again when a site admin changes it.

Everything works from the keyboard, too: with a widget focused, the arrow keys move it (Up and Down, and Left and
Right past either end, move it to the section before or after), Shift with Left or Right resizes it, and Delete
removes it. Screen readers hear each change announced.

Settings → Widgets decides which widgets are available to everyone. Every widget is available until a site admin
switches it off; a switched-off widget is left out of every palette and every layout. A widget that becomes available later shows up in the palette of admins who have customized,
never in their layout. Sections without widgets are dropped on save, and a layout holds at most 20 sections.

## Quick Links

The Quick Links cell is a simple list of links that have been added to the `Content` section of the sidebar menu. It is automatically generated and displayed.

## Widgets

The `stats`, `logs` and `charts` cells are elements that display information on the dashboard. They are either a small informational card, or a chart of some sort.

### Settings

Each of the widget types has some basic settings in the administration settings area. Each type of widget has its own specific settings, which can be used to specify which information is displayed for the widget, as well as whether it's animated, where labels are displayed, etc.

### Statistics

The first type of widget is the statistics widget. This is a counter with a shortcut that allows the user to open the
list of elements of a module. It could be used to display a year-to-day revenue at a glance, total number of users
in the system, new users this month, or any other simple statistic. It can also show tables or other types of data
passed to it's parameter `value` as a string.

Adding a new widget is done by editing the `initAdmin` method of your `Module.php` file, similar to adding a menu. To display the total number of users in the system, you could use the following code:

```php
$statsItem = new StatsItem([
    'bgColor' => 'bg-blue',
    'title' => 'Users',
    'value' => model('UserModel')->countAll(),
    'url' => ADMIN_AREA . '/users',
    'faIcon' => 'fa fa-user',
]);
service('widgets')->widget('stats')->collection('stats')->addItem($statsItem);
```

When defining a new StatsItem, you can pass the following parameters:

**bgColor**

`bgColor` represents the background color of the widget.
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

The value of the widget displayed under the title: typically the total number of records. It can also any string, like a table with data.

**url**

The address that displays the list of objects on your module. It will be associated with the word "view detail".

**faIcon**

The FontAwesome icon to display next to the title.

### Charts

Adding a new chart widget is done in the `initAdmin` method of your `Module.php` file, similar to adding a menu:

```php
$statsItem = new ChartsItem([
    'title'   => 'User classification by group',
    'type'   => 'line',
    'cssClass'   => 'col-6',
]);
$statsItem->addDataset('auth_groups_users', 'group', 'user_id');
service('widgets')->widget('charts')->collection('charts')->addItem($statsItem);
```

The following parameters can be passed to the `ChartsItem` constructor:

**title**

The title of the chart displayed at the top center

**type**

The type of chart.
The available options are:

- line
- bar
- doughnut
- pie
- polarArea

**cssClass**

Set the width of the chart.
Examples options are:

- col-3
- col-6
- col-9
- col-12

**addDataset(string $tableName, string $groupField, string $countField, string $selectMode = 'count')**

Currently, only one dataset can be added to the chart. The required parameters are:

- Name of the table
- Grouping field
- Field to count

It is possible to indicate a fourth parameter to choose the counting mode; the possible values are:

- count
- avg
- max
- min
- sum

- if indicate **count** then **selectCount($field)** is executed
- if indicate **avg** then **selectAvg($field)** is executed
- if indicate **max** then **selectMax($field)** is executed
- if indicate **min** then **selectMin($field)** is executed
- if indicate **sum** then **selectSum($field)** is executed
